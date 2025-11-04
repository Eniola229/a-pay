<?php

namespace App\Http\Controllers\Ussd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Mail\AirtimePurchaseMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Client\RequestException;
use App\Services\CashbackService;
use App\Models\Errors;
use App\Models\Transaction;
use App\Models\AirtimePurchase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Mail\DataPurchaseMail;
use Illuminate\Support\Str;
use App\Models\DataPurchase;

class UssdController extends Controller
{
    public function handle(Request $request)
    {
        // === Incoming data from Africa’s Talking ===
        $sessionId   = $request->input('sessionId');
        $serviceCode = $request->input('serviceCode');
        $phoneNumber = $request->input('phoneNumber');
        $text        = trim($request->input('text')); // Example: "1*2*100"

        // Log incoming request for debugging
        //Log::info('USSD Request Data', ['request' => $request->all()]);

        // Split user input by "*" to track the current step
        $inputs = $text === "" ? [] : explode('*', $text);

        // === Stage 0: Main Menu ===
        if (count($inputs) === 0) {
            $response  = "CON Welcome to A-Pay\n";
            $response .= "1. Buy Airtime\n";
            $response .= "2. Buy Data\n";
            $response .= "3. Pay Electricity Bill\n";
            $response .= "4. Check Balance\n";
            $response .= "5. Borrow Airtime/Data";
        }

        // === Stage 1: Handle first option selection ===
        else {
            switch ($inputs[0]) {
                case "1": // Buy Airtime
                    $response = $this->buyAirtimeMenu($inputs, $phoneNumber);
                    break;

                case "2": // Buy Data
                    $response = $this->buyDataMenu($inputs, $phoneNumber);
                    break;

                case "3": // Pay Electricity Bill
                    $response = $this->electricityMenu($inputs, $phoneNumber);
                    break;

                    case "4": // Check Balance
                        switch (count($inputs)) {
                            case 1:
                                return "CON Enter Your Transaction PIN \n\n0. Back\n00. Main Menu";

                            case 2:
                                if ($inputs[1] === "0") return $this->goBack("1");
                                if ($inputs[1] === "00") return $this->mainMenu();

                                $user = User::where('mobile', $phoneNumber)->first();
                                if (!$user) return "END User not found.";

                                $balance = Balance::where('user_id', $user->id)->first();

                                // If no balance record, ask to create PIN
                                if (!$balance) {
                                    return "CON You don't have a PIN yet.\nPlease create a 4-digit PIN now:";
                                }

                                $pin = $inputs[1];
                                if (!is_numeric($pin) || !Hash::check($pin, $balance->pin)) {
                                    return "END Invalid PIN. Session ended.";
                                }

                                return "END Dear {$user->name}!\nYour wallet balance is ₦{$balance->balance}.\nThank you for using A-Pay!";

                            case 3:
                                // Handle new PIN creation when no balance record exists
                                $user = User::where('mobile', $phoneNumber)->first();
                                $newPin = $inputs[2];

                                if (strlen($newPin) !== 4 || !is_numeric($newPin)) {
                                    return "CON PIN must be 4 digits. Try again:";
                                }

                                // Create new balance record
                                $balance = new Balance();
                                $balance->user_id = $user->id;
                                $balance->balance = 0.00;
                                $balance->pin = Hash::make($newPin);
                                $balance->save();

                                return "END PIN created successfully!\nYou can now check your balance by dialing again.";

                            default:
                                return "END Invalid response. Try again.";
                        }


                case "5": // Borrow Airtime/Data
                    $response = $this->borrowMenu($inputs, $phoneNumber);
                    break;

                default:
                    $response = "END Invalid choice. Please dial again.";
                    break;
            }
        }

        // Return plain text response (important)
        return response($response)->header('Content-Type', 'text/plain');
    }

    //GET DATA PLAN
    public function getDataPlans($networkId)
    {
        // Normalize network ID
        $networkId = strtolower($networkId);

        // Fetch all data from the external API
        $response = Http::get('https://ebills.africa/wp-json/api/v2/variations/data');

        if ($response->failed()) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch data from provider.'
            ], 500);
        }

        // Access the 'data' key from the response
        $allData = $response->json()['data'] ?? [];

        // Filter only plans for the requested network
        $filteredPlans = collect($allData)->filter(function ($item) use ($networkId) {
            $serviceId = strtolower($item['service_id']); // Convert service_id to lowercase
            return $serviceId === $networkId;
        });


        // Reformat data to match the expected structure
        $formatted = [];
        foreach ($filteredPlans as $plan) {
            $planCode = $plan['variation_id'] ?? uniqid(); // fallback if variation_id is missing
            $formatted[$planCode] = [
                'name'  => $plan['data_plan'] ?? 'Unnamed Plan',
                'price' => $plan['price'] ?? 0,
            ];
        }

        if (empty($formatted)) {
            return response()->json([
                'status' => false,
                'message' => 'No plans found for this network.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $formatted
        ]);
    }


    // BUY AIRTIME FLOW
    private function buyAirtimeMenu(array $inputs, string $phoneNumber)
    {
        $networks = [
            "1" => "mtn",
            "2" => "glo",
            "3" => "airtel",
            "4" => "9mobile",
        ];

        // Helper: Normalize any Nigerian phone number format
        $normalize = function (string $num) {
            $num = preg_replace('/\D+/', '', $num); // remove all non-digits

            // If starts with 0, replace with +234
            if (strlen($num) === 11 && substr($num, 0, 1) === '0') {
                return '+234' . substr($num, 1);
            }

            // If starts with 234, add +
            if (strlen($num) >= 10 && substr($num, 0, 3) === '234') {
                return '+' . $num;
            }

            // If starts with 8 or 7 or 9 (no 0), assume missing +234
            if (strlen($num) === 10 && in_array(substr($num, 0, 1), ['7', '8', '9'])) {
                return '+234' . $num;
            }

            // Already international or unknown format
            if (strpos($num, '+') === 0) {
                return $num;
            }

            return '+234' . $num; // fallback
        };

        switch (count($inputs)) {
            case 1:
                // Step 1: Choose Network
                return "CON Select Network\n1. MTN\n2. Glo\n3. Airtel\n4. 9mobile\n\n0. Back\n00. Main Menu";

            case 2:
                // Step 2: Enter recipient phone number
                if ($inputs[1] === "0") return $this->goBack();
                if ($inputs[1] === "00") return $this->mainMenu();
                if (!isset($networks[$inputs[1]])) return "END Invalid network. Session ended.";

                return "CON Enter recipient phone number or press 1 to send to your number:\n\n0. Back\n00. Main Menu";

            case 3:
                if ($inputs[2] === "0") return $this->goBack("1");
                if ($inputs[2] === "00") return $this->mainMenu();

                $recipientRaw = trim($inputs[2]);

                if ($recipientRaw === "1") {
                    $recipient = $phoneNumber;
                } else {
                    $recipient = $normalize($recipientRaw);
                }

                // Store normalized recipient into the array (so we can be use later)
                $inputs[2] = $recipient;

                return "CON Enter amount (₦)\n\n0. Back\n00. Main Menu";

            case 4:
                if ($inputs[3] === "0") return $this->goBack("2");
                if ($inputs[3] === "00") return $this->mainMenu();

                $amount = $inputs[3];
                if (!is_numeric($amount) || $amount < 10) {
                    return "END Invalid amount. Minimum is ₦10.";
                }

                return "CON Enter your 4-digit PIN\n\n0. Back\n00. Main Menu";

            case 5:
                if ($inputs[4] === "0") return $this->goBack("3");
                if ($inputs[4] === "00") return $this->mainMenu();

                $network = $networks[$inputs[1]];
                $recipient = $inputs[2];
                $amount = $inputs[3];
                $pin = $inputs[4];

                return "CON Confirm: Buy ₦{$amount} airtime for {$recipient} on " . strtoupper($network) . "\n1. Confirm\n2. Cancel\n\n0. Back\n00. Main Menu";

            case 6:
                $choice = $inputs[5];
                $network = $networks[$inputs[1]];
                $recipient = $inputs[2];
                $amount = $inputs[3];
                $pin = $inputs[4];

                if ($choice == "0") return $this->goBack("4");
                if ($choice == "00") return $this->mainMenu();
                if ($choice == "2") return "END Transaction cancelled. Thank you.";
                if ($choice != "1") return "END Invalid choice. Session ended.";

                // ===== Processing =====
                $user = User::where('mobile', $phoneNumber)->first();
                if (!$user) return "END User not found.";

                $balance = Balance::where('user_id', $user->id)->first();
                if (!$balance) return "END You don't have a wallet yet. Please create a PIN first.";

                if (!Hash::check($pin, $balance->pin)) {
                    return "END Invalid PIN. Session ended.";
                }

                if ($balance->balance < $amount) {
                    return "END Insufficient balance. Please fund your wallet.";
                }

                DB::beginTransaction();
                try {
                    $balance->balance -= $amount;
                    $balance->save();

                    $airtime = AirtimePurchase::create([
                        'user_id' => $user->id,
                        'phone_number' => $recipient,
                        'amount' => $amount,
                        'network_id' => $network,
                        'status' => 'PENDING',
                    ]);

                    $transaction = Transaction::create([
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'beneficiary' => $recipient,
                        'description' => strtoupper($network) . " airtime purchase for {$recipient}",
                        'status' => 'PENDING',
                    ]);

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . env('EBILLS_API_TOKEN'),
                        'Content-Type' => 'application/json',
                    ])->post('https://ebills.africa/wp-json/api/v2/airtime', [
                        'request_id' => 'req_' . uniqid(),
                        'phone' => $recipient,
                        'service_id' => $network,
                        'amount' => $amount,
                    ]);

                    if ($response->successful() && data_get($response->json(), 'code') === 'success') {
                        $transaction->update(['status' => 'SUCCESS']);
                        $airtime->update(['status' => 'SUCCESS']);

                        $cashback = CashbackService::calculate($amount);
                        if ($cashback > 0) {
                            $balance->balance += $cashback;
                            $balance->save();
                            $transaction->cash_back = ($transaction->cash_back ?? 0) + $cashback;
                            $transaction->save();
                        }

                        DB::commit();
                        return "END Success! ₦{$amount} airtime to {$recipient} is being processed.";
                    } else {
                        $balance->balance += $amount;
                        $balance->save();
                        $transaction->update(['status' => 'ERROR']);
                        $airtime->update(['status' => 'FAILED']);
                        DB::commit();
                        return "END Airtime purchase failed. Please try again later.";
                    }

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Airtime USSD error', ['error' => $e->getMessage()]);
                    return "END An error occurred. Please try again later.";
                }

            default:
                return "END Invalid response. Try again.";
        }
    }

    //BUY DATA FLOW
    private function buyDataMenu(array $inputs, string $phoneNumber)
    {
        $networks = [
            "1" => "mtn",
            "2" => "glo",
            "3" => "airtel",
            "4" => "9mobile",
            "5" => "smile",
        ];

        // === Helper to normalize any Nigerian phone number ===
        $normalize = function (string $num) {
            $num = preg_replace('/\D+/', '', $num);
            if (strlen($num) === 11 && str_starts_with($num, '0')) return '+234' . substr($num, 1);
            if (strlen($num) === 10 && in_array(substr($num, 0, 1), ['7', '8', '9'])) return '+234' . $num;
            if (str_starts_with($num, '234')) return '+' . $num;
            if (str_starts_with($num, '+')) return $num;
            return '+234' . $num;
        };

        switch (count($inputs)) {
            case 1:
                // Select Network
                return "CON Select Data Network\n1. MTN\n2. Glo\n3. Airtel\n4. 9mobile\n5. Smile\n\n0. Back\n00. Main Menu";

            case 2:
                if ($inputs[1] === "0") return $this->goBack();
                if ($inputs[1] === "00") return $this->mainMenu();
                if (!isset($networks[$inputs[1]])) return "END Invalid network. Session ended.";

                return "CON Enter recipient phone number or press 1 to use your number:\n\n0. Back\n00. Main Menu";

            case 3:
                if ($inputs[2] === "0") return $this->goBack("1");
                if ($inputs[2] === "00") return $this->mainMenu();

                $recipientRaw = trim($inputs[2]);
                $recipient = ($recipientRaw === "1") ? $phoneNumber : $normalize($recipientRaw);
                $inputs[2] = $recipient;

                // Load data plans from your own API controller
                $network = $networks[$inputs[1]];
                $response = Http::get(url("/api/data-plans/{$network}"));

                if ($response->failed()) return "END Failed to load data plans.";
                $data = $response->json();

                if (empty($data['data'])) return "END No data plans found.";

                $plans = array_values($data['data']); // numeric index
                session([
                    'ussd_data_plans' => $plans,
                    'ussd_page' => 1,
                ]);

                return $this->showDataPlansPage(1, $plans);

            case 4:
                $plans = session('ussd_data_plans', []);
                $page = session('ussd_page', 1);
                $perPage = 5;
                $totalPages = ceil(count($plans) / $perPage);

                // Handle pagination control
                if ($inputs[3] === "1" && $page < $totalPages) {
                    $page++;
                    session(['ussd_page' => $page]);
                    return $this->showDataPlansPage($page, $plans);
                }

                if ($inputs[3] === "9" && $page > 1) {
                    $page--;
                    session(['ussd_page' => $page]);
                    return $this->showDataPlansPage($page, $plans);
                }

                $planIndex = intval($inputs[3]) - 1;
                $planList = array_values($plans);

                if (!isset($planList[$planIndex])) return "END Invalid data plan selected.";

                $plan = $planList[$planIndex];
                $inputs[3] = $plan;

                return "CON Enter your 4-digit PIN\n\n0. Back\n00. Main Menu";

            // === PIN entry and purchase confirmation logic ===
            // (same as your existing case 5 & 6 code)
        }
    }

    /**
     * Helper to paginate data plans (5 per page)
     */
    private function showDataPlansPage(int $page, array $plans)
    {
        $perPage = 5;
        $total = count($plans);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        $slice = array_slice($plans, $offset, $perPage, true);

        $menu = "CON Select Data Plan (Page {$page}/{$totalPages})\n";
        $i = $offset + 1;

        foreach ($slice as $plan) {
            $menu .= "{$i}. {$plan['name']} - ₦{$plan['price']}\n";
            $i++;
        }

        if ($page < $totalPages) $menu .= "\n1. Next Page";
        if ($page > 1) $menu .= "\n9. Prev Page";

        $menu .= "\n\n0. Back\n00. Main Menu";

        return $menu;
    }



    // PAY ELECTRICITY BILL FLOW
    private function electricityMenu(array $inputs, string $phoneNumber)
    {
        switch (count($inputs)) {
            case 1:
                return "CON Select DISCO\n1. Ikeja\n2. Abuja\n3. Eko\n4. Ibadan\n\n0. Back\n00. Main Menu";
            case 2:
                return "CON Enter Meter Number\n\n0. Back\n00. Main Menu";
            case 3:
                $meter = $inputs[2];
                return "CON Enter Amount (₦)\n\n0. Back\n00. Main Menu";
            case 4:
                $amount = $inputs[3];
                return "CON Confirm ₦{$amount} payment to Meter {$inputs[2]}\n1. Confirm\n2. Cancel\n\n0. Back\n00. Main Menu";
            case 5:
                if ($inputs[4] == "1") return "END Payment processing... You’ll get SMS shortly.";
                if ($inputs[4] == "2") return "END Transaction cancelled.";
                return "END Invalid option.";
            default:
                return "END Invalid input.";
        }
    }

    // BORROW AIRTIME/DATA FLOW
    private function borrowMenu(array $inputs, string $phoneNumber)
    {
        switch (count($inputs)) {
            case 1:
                return "CON Choose Option\n1. Borrow Airtime\n2. Borrow Data\n\n0. Back\n00. Main Menu";
            case 2:
                if ($inputs[1] == "1") {
                    return "CON Enter Airtime Amount to Borrow (₦)\n\n0. Back\n00. Main Menu";
                } elseif ($inputs[1] == "2") {
                    return "CON Enter Data Size to Borrow (MB)\n\n0. Back\n00. Main Menu";
                } else {
                    return "END Invalid choice.";
                }
            case 3:
                $type = $inputs[1] == "1" ? "Airtime" : "Data";
                $value = $inputs[2];
                return "CON Confirm to borrow {$type} worth {$value}\n1. Confirm\n2. Cancel\n\n0. Back\n00. Main Menu";
            case 4:
                if ($inputs[3] == "1") return "END Borrow request submitted successfully.";
                if ($inputs[3] == "2") return "END Request cancelled.";
                return "END Invalid input.";
            default:
                return "END Invalid input.";
        }
    }

    // Helper: Go Back (simplified message)
    private function goBack($step = "")
    {
        return "END Going back not fully implemented yet (Step {$step}). Dial again to continue.";
    }

    // Helper: Main Menu shortcut
    private function mainMenu()
    {
        return "CON Welcome to A-Pay\n1. Buy Airtime\n2. Buy Data\n3. Pay Electricity Bill\n4. Check Balance\n5. Borrow Airtime/Data";
    }
}
