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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class UssdController extends Controller
{
        public function handle(Request $request)
        {
            $sessionId   = $request->input('sessionId');
            $serviceCode = $request->input('serviceCode');
            $phoneNumber = $request->input('phoneNumber');
            $text        = trim($request->input('text'));

            // === Use Africa’s Talking session ID to store USSD session manually ===
            $key = "ussd_session_" . $sessionId;
            $inputs = $text === "" ? [] : explode('*', $text);

            // Retrieve existing session data
            $userSession = cache()->get($key, []);

            // === Stage 0: Main Menu ===
            if (count($inputs) === 0) {
                $userSession = []; // reset session data
                cache()->put($key, $userSession, now()->addMinutes(10)); // keep for 10 min

                $response  = "CON Welcome to A-Pay\n";
                $response .= "1. Buy Airtime\n";
                $response .= "2. Buy Data\n";
                $response .= "3. Pay Electricity Bill\n";
                $response .= "4. Check Balance\n";
                $response .= "5. Borrow Airtime/Data";
            } else {
                switch ($inputs[0]) {
                    case "1":
                        $response = $this->buyAirtimeMenu($inputs, $phoneNumber, $userSession);
                        break;
                    case "2":
                        $response = $this->buyDataMenu($inputs, $phoneNumber, $userSession);
                        break;
                    case "3":
                        $response = $this->electricityMenu($inputs, $phoneNumber, $userSession);
                        break;
                    case "4":
                                               switch (count($inputs)) {
                            case 1:
                                return "CON Enter Your Transaction PIN \n\n0. Back\n00. Main Menu";

                            case 2:
                                if ($inputs[1] === "0") return $this->goBack("1");
                                if ($inputs[1] === "00") return $this->mainMenu();

                                $user = User::where('mobile', $phoneNumber)->first();
                                if (!$user) return "END User not found.";

                                $balance = Balance::where('user_id', $user->id)->first();

                                if (!$balance) {
                                    return "CON You don't have a PIN yet.\nPlease create a 4-digit PIN now:";
                                }

                                $pin = $inputs[1];
                                if (!is_numeric($pin) || !Hash::check($pin, $balance->pin)) {
                                    return "END Invalid PIN. Session ended.";
                                }

                                return "END Dear {$user->name}!\nYour wallet balance is ₦{$balance->balance}.\nThank you for using A-Pay!";

                            case 3:
                                $user = User::where('mobile', $phoneNumber)->first();
                                $newPin = $inputs[2];

                                if (strlen($newPin) !== 4 || !is_numeric($newPin)) {
                                    return "CON PIN must be 4 digits. Try again:";
                                }

                                $balance = new Balance();
                                $balance->user_id = $user->id;
                                $balance->balance = 0.00;
                                $balance->pin = Hash::make($newPin);
                                $balance->save();

                                return "END PIN created successfully!\nYou can now check your balance by dialing again.";

                            default:
                                return "END Invalid response. Try again.";
                        }
                        break;
                    case "5":
                        $response = $this->borrowMenu($inputs, $phoneNumber, $userSession);
                        break;
                    default:
                        $response = "END Invalid choice. Please dial again.";
                        break;
                }
            }

            // Save session if not ended
            if (!str_starts_with($response, 'END')) {
                cache()->put($key, $userSession, now()->addMinutes(10));
            } else {
                cache()->forget($key);
            }

            return response($response)->header('Content-Type', 'text/plain');
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
    // --------------------------------------------------------------
    // Helper: Normalize Nigerian numbers to +234xxxxxxxxxx
    // --------------------------------------------------------------
    $normalize = function ($num) use ($phoneNumber) {
        $num = preg_replace('/\D+/', '', $num);
        if ($num === '') return $phoneNumber;
        if (strlen($num) === 11 && $num[0] === '0') return '+234' . substr($num, 1);
        if (strlen($num) === 10 && in_array($num[0], ['7', '8', '9'])) return '+234' . $num;
        if (str_starts_with($num, '234')) return '+' . $num;
        if (str_starts_with($num, '+')) return $num;
        return '+234' . $num;
    };

    // --------------------------------------------------------------
    // Log everything (useful for debugging)
    // --------------------------------------------------------------
    \Log::info('USSD BUY-DATA', [
        'phone'       => $phoneNumber,
        'inputs'      => $inputs,
        'count'       => count($inputs),
        'last'        => $inputs ? end($inputs) : null,
        'session_id'  => session()->getId(),
        'has_plans'   => session()->has('ussd_data_plans'),
        'page'        => session('ussd_page', 'none'),
    ]);

    // --------------------------------------------------------------
    // Clean USSD input (remove dial codes)
    // --------------------------------------------------------------
    $inputs = array_values(array_filter($inputs, fn($i) => !preg_match('/^\*\d+\*$/', $i) && $i !== '*384*7178#'));
    $inputCount = count($inputs);
    $lastInput  = $inputs ? trim(end($inputs)) : '';

    // --------------------------------------------------------------
    // Available networks
    // --------------------------------------------------------------
    $networks = [
        '1' => 'mtn',
        '2' => 'glo',
        '3' => 'airtel',
        '4' => '9mobile',
        '5' => 'smile',
    ];

    // ==============================================================
    // MAIN FLOW
    // ==============================================================
    switch ($inputCount) {
        // ----------------------------------------------------------
        // 0 – First screen: choose network
        // ----------------------------------------------------------
        case 0:
            return "CON Select Data Network\n"
                . "1. MTN\n2. Glo\n3. Airtel\n4. 9mobile\n5. Smile\n\n"
                . "0. Back\n00. Main Menu";

        // ----------------------------------------------------------
        // 1 – Network selected → ask for recipient
        // ----------------------------------------------------------
        case 1:
            if ($lastInput === '0') return $this->goBack();
            if ($lastInput === '00') return $this->mainMenu();
            if (!isset($networks[$lastInput])) return "END Invalid network. Session ended.";

            session(['ussd_network' => $networks[$lastInput]]);

            return "CON Enter recipient phone number or press 1 to use your number:\n\n"
                . "0. Back\n00. Main Menu";

        // ----------------------------------------------------------
        // 2 – Phone number received → fetch data plans
        // ----------------------------------------------------------
        case 2:
            if ($lastInput === '0') return $this->goBack('1');
            if ($lastInput === '00') return $this->mainMenu();

            $recipient = ($lastInput === '1') ? $phoneNumber : $normalize($lastInput);
            $networkId = session('ussd_network') ?? $networks[$inputs[0]] ?? null;

            if (!$networkId) {
                return "END Session expired. Please start again.";
            }

            $api = $this->getDataPlans($networkId);
            if (!$api['status'] || empty($api['data'])) return "END No data plans available.";

            $plans = array_map(
                fn($key, $p) => array_merge($p, ['variation_id' => $key]),
                array_keys($api['data']),
                $api['data']
            );

            session([
                'ussd_data_plans' => $plans,
                'ussd_recipient'  => $recipient,
                'ussd_network'    => $networkId,
                'ussd_page'       => 1,
            ]);

            return $this->showDataPlansPage(1, $plans);

        // ----------------------------------------------------------
        // 3+ – Pagination / Plan selection / PIN entry
        // ----------------------------------------------------------
        default:
            // ✅ Safety check — restart session if lost
            if (!session()->has('ussd_data_plans') || !session()->has('ussd_network')) {
                \Log::warning('USSD session lost — restarting', ['phone' => $phoneNumber]);
                return "END Session expired. Please start again.";
            }

            $plans = session('ussd_data_plans', []);
            $page = max(1, (int) session('ussd_page', 1));
            $perPage = 5;
            $totalPages = $plans ? ceil(count($plans) / $perPage) : 1;

            if (in_array($lastInput, ['8', '9'])) {
                if ($lastInput === '9' && $page < $totalPages) $page++;
                elseif ($lastInput === '8' && $page > 1) $page--;
                session(['ussd_page' => $page]);
                return $this->showDataPlansPage($page, $plans);
            }

            if ($lastInput === '0') return $this->goBack('2');
            if ($lastInput === '00') return $this->mainMenu();

            // Plan selection
            if (!is_numeric($lastInput)) return "END Invalid input. Enter a number.";
            $selection = (int)$lastInput;
            $offset = ($page - 1) * $perPage;
            $idx = $offset + ($selection - 1);

            if (!isset($plans[$idx])) return "END Invalid plan selected.";

            $selected = $plans[$idx];
            session(['ussd_selected_plan' => $selected]);

            // Next stage — ask for PIN
            return "CON Enter your 4-digit PIN\n\n0. Back\n00. Main Menu";

        // ----------------------------------------------------------
        // 4 – PIN entered → process data purchase
        // ----------------------------------------------------------
        case 4:
            $pin = $lastInput;
            if (!is_numeric($pin) || strlen($pin) !== 4) return "END Invalid PIN. Must be 4 digits.";

            if (!session()->has('ussd_selected_plan') || !session()->has('ussd_recipient')) {
                return "END Session expired. Please start again.";
            }

            $user = User::where('mobile', $normalize($phoneNumber))
                ->orWhere('mobile', $phoneNumber)
                ->first();
            if (!$user) return "END User not found.";

            $bal = Balance::where('user_id', $user->id)->first();
            if (!$bal || !Hash::check($pin, $bal->pin)) return "END Invalid PIN.";

            $plan = session('ussd_selected_plan');
            $networkId = session('ussd_network');
            $recipient = session('ussd_recipient');
            $price = $plan['price'];
            $varId = $plan['variation_id'];
            $name = $plan['name'];

            if ($bal->balance < $price) return "END Insufficient balance. ₦{$bal->balance}";

            $bal->decrement('balance', $price);

            $dp = DataPurchase::create([
                'user_id'      => $user->id,
                'phone_number' => $recipient,
                'data_plan_id' => $varId,
                'network_id'   => $networkId,
                'amount'       => $price,
                'status'       => 'PENDING',
            ]);

            $tx = Transaction::create([
                'user_id'     => $user->id,
                'amount'      => $price,
                'beneficiary' => $recipient,
                'description' => "Data: {$name}",
                'status'      => 'PENDING',
            ]);

            $payload = [
                'request_id'   => 'REQ_' . strtoupper(Str::random(12)),
                'phone'        => $recipient,
                'service_id'   => $networkId,
                'variation_id' => $varId,
            ];

            try {
                $resp = Http::withToken(env('EBILLS_API_TOKEN'))
                    ->timeout(15)
                    ->post('https://ebills.africa/wp-json/api/v2/data', $payload)
                    ->json();
            } catch (\Exception $e) {
                $bal->increment('balance', $price);
                $dp->update(['status' => 'FAILED']);
                $tx->update(['status' => 'ERROR']);
                return "END Service unavailable. Refunded.";
            }

            if (($resp['code'] ?? '') === 'success') {
                $dp->update(['status' => 'SUCCESS']);
                $tx->update(['status' => 'SUCCESS']);

                session()->forget([
                    'ussd_data_plans',
                    'ussd_network',
                    'ussd_recipient',
                    'ussd_page',
                    'ussd_selected_plan',
                ]);

                return "END SUCCESS!\n{$name} sent to {$recipient}\nBalance: ₦{$bal->balance}";
            }

            $bal->increment('balance', $price);
            $dp->update(['status' => 'FAILED']);
            $tx->update(['status' => 'ERROR']);
            return "END Failed. Refunded.\n" . ($resp['message'] ?? 'Unknown error');
    }
}

/**
 * Paginated Data Plan Menu
 */
private function showDataPlansPage(int $page, array $plans): string
{
    $perPage = 5;
    $total = count($plans);
    $totalPages = $total ? ceil($total / $perPage) : 1;
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    $slice = array_slice($plans, $offset, $perPage);

    $menu = "CON Select Data Plan (Page {$page}/{$totalPages})\n";
    $i = 1;
    foreach ($slice as $p) {
        $menu .= "{$i}. {$p['name']} - ₦{$p['price']}\n";
        $i++;
    }
    $menu .= "\n";

    if ($page < $totalPages) $menu .= "9. Next Page\n";
    if ($page > 1) $menu .= "8. Prev Page\n";

    $menu .= "\n0. Back\n00. Main Menu";
    return $menu;
}

    // Fetch Data Plans
    public function getDataPlans($networkId)
    {
        return Cache::remember("data_plans_{$networkId}", 600, function () use ($networkId) {
            $networkId = strtolower($networkId);

            try {
                $response = Http::timeout(10)
                    ->get('https://ebills.africa/wp-json/api/v2/variations/data');

                if ($response->failed()) {
                    return [
                        'status' => false,
                        'message' => 'Failed to fetch data from provider.',
                        'data' => []
                    ];
                }

                $allData = $response->json()['data'] ?? [];

                $filteredPlans = collect($allData)->filter(function ($item) use ($networkId) {
                    return strtolower($item['service_id']) === $networkId;
                });

                $formatted = [];
                foreach ($filteredPlans as $plan) {
                    $planCode = $plan['variation_id'] ?? uniqid();
                    $formatted[$planCode] = [
                        'name'  => $plan['data_plan'] ?? 'Unnamed Plan',
                        'price' => $plan['price'] ?? 0,
                    ];
                }

                return [
                    'status' => true,
                    'message' => 'OK',
                    'data' => $formatted
                ];
            } catch (\Exception $e) {
                return [
                    'status' => false,
                    'message' => 'Connection error: ' . $e->getMessage(),
                    'data' => []
                ];
            }
        });
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
