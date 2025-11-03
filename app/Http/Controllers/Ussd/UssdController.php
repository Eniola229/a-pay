<?php

namespace App\Http\Controllers\Ussd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UssdController extends Controller
{
    public function handle(Request $request)
    {
        // === Incoming data from Africa’s Talking ===
        $sessionId   = $request->input('sessionId');
        $serviceCode = $request->input('serviceCode');
        $phoneNumber = $request->input('phoneNumber');
        $text        = trim($request->input('text')); // Example: "1*2*100"

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
                    $balance = "₦1,250.00"; // Replace with your wallet logic
                    $response = "END Your wallet balance is {$balance}.\nThank you for using A-Pay!";
                    break;

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

    // BUY AIRTIME FLOW
    private function buyAirtimeMenu(array $inputs, string $phoneNumber)
    {
        $networks = [
            "1" => "MTN",
            "2" => "Glo",
            "3" => "Airtel",
            "4" => "9mobile",
        ];

        switch (count($inputs)) {
            case 1:
                // Step 1: Choose Network
                return "CON Select Network\n1. MTN\n2. Glo\n3. Airtel\n4. 9mobile\n\n0. Back\n00. Main Menu";

            case 2:
                if ($inputs[1] === "0") return $this->goBack();
                if ($inputs[1] === "00") return $this->mainMenu();
                if (!isset($networks[$inputs[1]])) return "END Invalid network. Session ended.";
                return "CON Enter amount (₦)\n\n0. Back\n00. Main Menu";

            case 3:
                if ($inputs[2] === "0") return $this->goBack("1");
                if ($inputs[2] === "00") return $this->mainMenu();

                $network = $networks[$inputs[1]];
                $amount = $inputs[2];
                if (!is_numeric($amount) || $amount <= 0)
                    return "END Invalid amount. Session ended.";

                return "CON Confirm: Buy ₦{$amount} Airtime on {$network}\n1. Confirm\n2. Cancel\n\n0. Back\n00. Main Menu";

            case 4:
                $confirm = $inputs[3];
                $network = $networks[$inputs[1]];
                $amount = $inputs[2];

                if ($confirm == "1") {
                    //Call your VTU API here
                    return "END Success! ₦{$amount} Airtime for {$network} is being processed.";
                } elseif ($confirm == "2") {
                    return "END Transaction cancelled. Thank you.";
                } elseif ($confirm == "0") {
                    return $this->goBack("2");
                } elseif ($confirm == "00") {
                    return $this->mainMenu();
                } else {
                    return "END Invalid choice.";
                }

            default:
                return "END Invalid response. Try again.";
        }
    }

    // ===============================================================
    // 2️⃣  BUY DATA FLOW
    // ===============================================================
    private function buyDataMenu(array $inputs, string $phoneNumber)
    {
        switch (count($inputs)) {
            case 1:
                return "CON Choose Data Network\n1. MTN\n2. Glo\n3. Airtel\n4. 9mobile\n\n0. Back\n00. Main Menu";
            case 2:
                return "CON Enter Data Amount (₦)\n\n0. Back\n00. Main Menu";
            case 3:
                $network = $inputs[1];
                $amount = $inputs[2];
                return "CON Confirm ₦{$amount} Data on {$network}\n1. Confirm\n2. Cancel\n\n0. Back\n00. Main Menu";
            case 4:
                if ($inputs[3] == "1") return "END Success! Your data purchase is processing.";
                if ($inputs[3] == "2") return "END Transaction cancelled.";
                return "END Invalid choice.";
            default:
                return "END Invalid input.";
        }
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
