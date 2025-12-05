  public function whatsappCallback(Request $request)
    {
        $reference = $request->query('reference');
        \Log::info('Paystack callback received:', $request->all());


        if (!$reference) {
            return response()->json(['error' => 'No payment reference provided.'], 400);
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.paystack.co/transaction/verify/{$reference}", [
            'headers' => [
                'Authorization' => 'Bearer ' . env('PAYSTACK_SECRET_KEY')
            ]
        ]);

        $body = json_decode($response->getBody(), true);

        $transaction = Transaction::where('reference', $reference)->latest()->first();

        if (!$transaction) {
            $html = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8" />
              <meta name="viewport" content="width=device-width,initial-scale=1" />
              <title>A-Pay Notification</title>
              <style>
                :root {
                  --primary: #0d9b3d;
                  --primary-dark: #0a7e33;
                  --bg: #f3fef6;
                  --text-dark: #052e16;
                  --text-muted: #64748b;
                  --warning: #facc15;
                  --card: #ffffff;
                  --shadow: rgba(13, 155, 61, 0.15);
                }
                body {
                  margin: 0;
                  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
                  background-color: var(--bg);
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  padding: 20px;
                  color: var(--text-dark);
                }
                .notification {
                  background: var(--card);
                  border-radius: 14px;
                  box-shadow: 0 8px 40px var(--shadow);
                  max-width: 480px;
                  width: 100%;
                  overflow: hidden;
                  animation: fadeIn 0.5s ease-in-out;
                }
                .header {
                  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                  color: #fff;
                  padding: 22px;
                  text-align: center;
                }
                .header h1 {
                  margin: 0;
                  font-size: 22px;
                  letter-spacing: 0.5px;
                }
                .content {
                  padding: 24px;
                  text-align: center;
                }
                .icon {
                  background: #fefce8;
                  color: var(--warning);
                  border-radius: 50%;
                  width: 72px;
                  height: 72px;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  font-size: 36px;
                  margin: 0 auto 16px;
                }
                h2 {
                  margin: 0;
                  font-size: 20px;
                  font-weight: 700;
                  color: var(--text-dark);
                }
                p {
                  color: var(--text-muted);
                  font-size: 15px;
                  margin: 10px 0 20px;
                  line-height: 1.5;
                }
                .details {
                  background: #f9fefb;
                  border: 1px solid #dcfce7;
                  border-radius: 10px;
                  padding: 12px 16px;
                  text-align: left;
                  font-size: 14px;
                  color: var(--text-muted);
                  margin-bottom: 18px;
                }
                .details strong {
                  color: var(--text-dark);
                }
                .btn {
                  background: var(--primary);
                  color: #fff;
                  border: none;
                  padding: 12px 24px;
                  border-radius: 10px;
                  font-weight: 600;
                  cursor: pointer;
                  transition: background 0.2s ease-in-out;
                }
                .btn:hover {
                  background: var(--primary-dark);
                }
                .footer {
                  font-size: 13px;
                  color: var(--text-muted);
                  margin-top: 18px;
                }
                @keyframes fadeIn {
                  from { opacity: 0; transform: translateY(20px); }
                  to { opacity: 1; transform: translateY(0); }
                }
              </style>
            </head>
            <body>
              <div class="notification">
                <div class="header">
                  <h1>A-Pay</h1>
                </div>
                <div class="content">
                  <div class="icon">⚠️</div>
                  <h2>Transaction Not Found</h2>
                  <p>We couldn’t locate any transaction matching your reference.  
                  Please verify your transaction ID or try again later.</p>

                  <div class="details">
                    <div><strong>Status:</strong> Not Found</div>
                    <div><strong>Code:</strong> TRANSACTION_NOT_FOUND</div>
                  </div>

                  <button class="btn" onclick="window.location.href='/'">Return to WhatsApp</button>

                  <div class="footer">
                    <p>© 2025 A-Pay Digital Services</p>
                  </div>
                </div>
              </div>
            </body>
            </html>
            HTML;

            return response($html, 404)->header('Content-Type', 'text/html; charset=utf-8');
        }

        if ($transaction->status == "SUCCESS") {
            $html = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8" />
              <meta name="viewport" content="width=device-width,initial-scale=1" />
              <title>A-Pay Notification</title>
              <style>
                :root {
                  --primary: #0d9b3d;
                  --primary-dark: #0a7e33;
                  --bg: #f3fef6;
                  --text-dark: #052e16;
                  --text-muted: #64748b;
                  --card: #ffffff;
                  --shadow: rgba(13, 155, 61, 0.15);
                }
                body {
                  margin: 0;
                  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
                  background-color: var(--bg);
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  padding: 20px;
                  color: var(--text-dark);
                }
                .notification {
                  background: var(--card);
                  border-radius: 14px;
                  box-shadow: 0 8px 40px var(--shadow);
                  max-width: 480px;
                  width: 100%;
                  overflow: hidden;
                  animation: fadeIn 0.5s ease-in-out;
                }
                .header {
                  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                  color: #fff;
                  padding: 22px;
                  text-align: center;
                }
                .header h1 {
                  margin: 0;
                  font-size: 22px;
                  letter-spacing: 0.5px;
                }
                .content {
                  padding: 24px;
                  text-align: center;
                }
                .icon {
                  background: #e8f9ee;
                  color: var(--primary);
                  border-radius: 50%;
                  width: 72px;
                  height: 72px;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  font-size: 36px;
                  margin: 0 auto 16px;
                }
                h2 {
                  margin: 0;
                  font-size: 20px;
                  font-weight: 700;
                  color: var(--text-dark);
                }
                p {
                  color: var(--text-muted);
                  font-size: 15px;
                  margin: 10px 0 20px;
                  line-height: 1.5;
                }
                .details {
                  background: #f9fefb;
                  border: 1px solid #dcfce7;
                  border-radius: 10px;
                  padding: 12px 16px;
                  text-align: left;
                  font-size: 14px;
                  color: var(--text-muted);
                  margin-bottom: 18px;
                }
                .details strong {
                  color: var(--text-dark);
                }
                .btn {
                  background: var(--primary);
                  color: #fff;
                  border: none;
                  padding: 12px 24px;
                  border-radius: 10px;
                  font-weight: 600;
                  cursor: pointer;
                  transition: background 0.2s ease-in-out;
                }
                .btn:hover {
                  background: var(--primary-dark);
                }
                .footer {
                  font-size: 13px;
                  color: var(--text-muted);
                  margin-top: 18px;
                }
                @keyframes fadeIn {
                  from { opacity: 0; transform: translateY(20px); }
                  to { opacity: 1; transform: translateY(0); }
                }
              </style>
            </head>
            <body>
              <div class="notification">
                <div class="header">
                  <h1>A-Pay</h1>
                </div>
                <div class="content">
                  <div class="icon">✅</div>
                  <h2>Transaction Already Processed</h2>
                  <p>✅ This transaction has already been completed successfully.  
                  Your funds have been sent — please check your wallet balance.</p>

                  <div class="details">
                    <div><strong>Status:</strong> Completed</div>
                    <div><strong>Code:</strong> TRANSACTION_ALREADY_COMPLETED</div>
                  </div>

                  <button class="btn" onclick="window.location.href='/'">Go to WhatsApp</button>

                  <div class="footer">
                    <p>© 2025 A-Pay Digital Services</p>
                  </div>
                </div>
              </div>
            </body>
            </html>
            HTML;

            return response($html, 409)->header('Content-Type', 'text/html; charset=utf-8');

        }

        // Get WhatsApp number stored in cache
        $mobile = \Cache::pull('whatsapp_topup_' . $transaction->id);

        if ($body['status'] && $body['data']['status'] === 'success') {

            $amount = $body['data']['amount'] / 100; // Convert kobo to Naira
            $user = $transaction->user;

            // Update transaction
            $transaction->status = "SUCCESS";
            $transaction->save();

            // Get or create balance
            $balance = Balance::firstOrCreate(
                ['user_id' => $user->id],
                ['id' => \Str::uuid(), 'balance' => 0, 'pin' => '']
            );

            $originalTopup = $amount;
            $totalDeducted = 0;

            // Repay unpaid loans
            $unpaidLoans = Borrow::where('user_id', $user->id)
                ->where('repayment_status', '!=', 'PAID')
                ->where('status', 'approved')
                ->orderBy('created_at', 'asc')
                ->get();

            $balanceOwe = Balance::where('user_id', $user->id)->first();

            foreach ($unpaidLoans as $loan) {
                if ($amount <= 0) break;

                $loanBalance = $loan->amount;

                if ($amount >= $loanBalance) {
                    $loan->repayment_status = 'PAID';
                    $amount -= $loanBalance;
                    $totalDeducted += $loanBalance;
                    if ($balanceOwe) $balanceOwe->owe -= $loanBalance;
                } else {
                    $loan->repayment_status = 'NOT PAID FULL';
                    $totalDeducted += $amount;
                    if ($balanceOwe) $balanceOwe->owe -= $amount;
                    $amount = 0;
                }

                $loan->save();
                if ($balanceOwe) $balanceOwe->save();
            }

            // Update balance with remaining amount
            $balance->balance += $amount;
            $balance->save();

            // Update credit limit
            $creditLimit = CreditLimit::firstOrNew(['user_id' => $user->id]);
            $creditLimit->id = $creditLimit->id ?? \Str::uuid();
            $creditLimit->limit_amount += $totalDeducted;
            $creditLimit->save();

            // Notify user via WhatsApp
            $message = "✅ Top-up successful! ₦" . number_format($originalTopup, 2) . " added to your wallet.";
            if ($totalDeducted > 0) {
                $message .= " ₦" . number_format($totalDeducted, 2) . " was deducted to repay your loan.";
            }

            if ($mobile) {
                $this->sendMessage($mobile, $message);
            }

            $html = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8" />
              <meta name="viewport" content="width=device-width,initial-scale=1" />
              <title>A-Pay Notification</title>
              <style>
                :root {
                  --primary: #0d9b3d;
                  --primary-dark: #0a7e33;
                  --bg: #f3fef6;
                  --text-dark: #052e16;
                  --text-muted: #64748b;
                  --card: #ffffff;
                  --shadow: rgba(13, 155, 61, 0.15);
                }
                body {
                  margin: 0;
                  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
                  background-color: var(--bg);
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  padding: 20px;
                  color: var(--text-dark);
                }
                .notification {
                  background: var(--card);
                  border-radius: 14px;
                  box-shadow: 0 8px 40px var(--shadow);
                  max-width: 480px;
                  width: 100%;
                  overflow: hidden;
                  animation: fadeIn 0.5s ease-in-out;
                }
                .header {
                  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                  color: #fff;
                  padding: 22px;
                  text-align: center;
                }
                .header h1 {
                  margin: 0;
                  font-size: 22px;
                  letter-spacing: 0.5px;
                }
                .content {
                  padding: 24px;
                  text-align: center;
                }
                .icon {
                  background: #e8f9ee;
                  color: var(--primary);
                  border-radius: 50%;
                  width: 72px;
                  height: 72px;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  font-size: 36px;
                  margin: 0 auto 16px;
                }
                h2 {
                  margin: 0;
                  font-size: 20px;
                  font-weight: 700;
                  color: var(--text-dark);
                }
                p {
                  color: var(--text-muted);
                  font-size: 15px;
                  margin: 10px 0 20px;
                  line-height: 1.5;
                }
                .details {
                  background: #f9fefb;
                  border: 1px solid #dcfce7;
                  border-radius: 10px;
                  padding: 12px 16px;
                  text-align: left;
                  font-size: 14px;
                  color: var(--text-muted);
                  margin-bottom: 18px;
                }
                .details strong {
                  color: var(--text-dark);
                }
                .btn {
                  background: var(--primary);
                  color: #fff;
                  border: none;
                  padding: 12px 24px;
                  border-radius: 10px;
                  font-weight: 600;
                  cursor: pointer;
                  transition: background 0.2s ease-in-out;
                }
                .btn:hover {
                  background: var(--primary-dark);
                }
                .footer {
                  font-size: 13px;
                  color: var(--text-muted);
                  margin-top: 18px;
                }
                @keyframes fadeIn {
                  from { opacity: 0; transform: translateY(20px); }
                  to { opacity: 1; transform: translateY(0); }
                }
              </style>
            </head>
            <body>
              <div class="notification">
                <div class="header">
                  <h1>A-Pay</h1>
                </div>
                <div class="content">
                  <div class="icon">🎉</div>
                  <h2>Transaction Successful</h2>
                  <p>{$message}</p>

                  <div class="details">
                    <div><strong>Status:</strong> Success</div>
                    <div><strong>Code:</strong> TRANSACTION_SUCCESSFUL</div>
                  </div>

                  <button class="btn" onclick="window.location.href='/'">Go to WhatsApp</button>

                  <div class="footer">
                    <p>© 2025 A-Pay Digital Services</p>
                  </div>
                </div>
              </div>
            </body>
            </html>
            HTML;

            return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');


        } else {
            // Payment failed
            $transaction->status = "ERROR";
            $transaction->save();

            if ($mobile) {
                $this->sendMessage($mobile, "❌ Top-up failed. Please try again.");
            }

            $html = <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <meta charset="utf-8" />
              <meta name="viewport" content="width=device-width,initial-scale=1" />
              <title>A-Pay Notification</title>
              <style>
                :root {
                  --primary: #0d9b3d;
                  --primary-dark: #0a7e33;
                  --bg: #f3fef6;
                  --text-dark: #052e16;
                  --text-muted: #64748b;
                  --danger: #dc2626;
                  --danger-light: #fee2e2;
                  --card: #ffffff;
                  --shadow: rgba(220, 38, 38, 0.15);
                }
                body {
                  margin: 0;
                  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
                  background-color: var(--bg);
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  height: 100vh;
                  padding: 20px;
                  color: var(--text-dark);
                }
                .notification {
                  background: var(--card);
                  border-radius: 14px;
                  box-shadow: 0 8px 40px var(--shadow);
                  max-width: 480px;
                  width: 100%;
                  overflow: hidden;
                  animation: fadeIn 0.5s ease-in-out;
                }
                .header {
                  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
                  color: #fff;
                  padding: 22px;
                  text-align: center;
                }
                .header h1 {
                  margin: 0;
                  font-size: 22px;
                  letter-spacing: 0.5px;
                }
                .content {
                  padding: 24px;
                  text-align: center;
                }
                .icon {
                  background: var(--danger-light);
                  color: var(--danger);
                  border-radius: 50%;
                  width: 72px;
                  height: 72px;
                  display: flex;
                  justify-content: center;
                  align-items: center;
                  font-size: 36px;
                  margin: 0 auto 16px;
                }
                h2 {
                  margin: 0;
                  font-size: 20px;
                  font-weight: 700;
                  color: var(--text-dark);
                }
                p {
                  color: var(--text-muted);
                  font-size: 15px;
                  margin: 10px 0 20px;
                  line-height: 1.5;
                }
                .details {
                  background: #f9fefb;
                  border: 1px solid #fee2e2;
                  border-radius: 10px;
                  padding: 12px 16px;
                  text-align: left;
                  font-size: 14px;
                  color: var(--text-muted);
                  margin-bottom: 18px;
                }
                .details strong {
                  color: var(--text-dark);
                }
                .btn {
                  background: var(--primary);
                  color: #fff;
                  border: none;
                  padding: 12px 24px;
                  border-radius: 10px;
                  font-weight: 600;
                  cursor: pointer;
                  transition: background 0.2s ease-in-out;
                }
                .btn:hover {
                  background: var(--primary-dark);
                }
                .footer {
                  font-size: 13px;
                  color: var(--text-muted);
                  margin-top: 18px;
                }
                @keyframes fadeIn {
                  from { opacity: 0; transform: translateY(20px); }
                  to { opacity: 1; transform: translateY(0); }
                }
              </style>
            </head>
            <body>
              <div class="notification">
                <div class="header">
                  <h1>A-Pay</h1>
                </div>
                <div class="content">
                  <div class="icon">❌</div>
                  <h2>Payment Failed</h2>
                  <p>We encountered an issue while processing your payment.  
                  Please verify your payment details or try again later.</p>

                  <div class="details">
                    <div><strong>Status:</strong> Failed</div>
                    <div><strong>Code:</strong> PAYMENT_FAILED</div>
                  </div>

                  <button class="btn" onclick="window.location.href='/'">Try Again</button>

                  <div class="footer">
                    <p>© 2025 A-Pay Digital Services</p>
                  </div>
                </div>
              </div>
            </body>
            </html>
            HTML;

            return response($html, 400)->header('Content-Type', 'text/html; charset=utf-8');

        }
    }