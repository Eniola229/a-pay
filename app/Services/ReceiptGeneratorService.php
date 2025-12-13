<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReceiptGeneratorService
{
    /**
     * Generate receipt image using API
     * 
     * @param array $data
     * @return string Path to generated image
     */
    public function generate(array $data)
    {
        $html = $this->generateReceiptHtml($data);
        
        // Use HTMLCSStoImage API
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode(env('HTMLCSS_USER_ID') . ':' . env('HTMLCSS_API_KEY'))
        ])->post('https://hcti.io/v1/image', [
            'html' => $html,
        ]);
        
        if ($response->successful()) {
            $imageUrl = $response->json()['url'];
            
            // Download the image
            $imageContent = file_get_contents($imageUrl);
            $filename = 'receipt_' . time() . '.png';
            $path = storage_path('app/temp/' . $filename);
            
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            file_put_contents($path, $imageContent);
            return $path;
        }
        
        throw new \Exception('Failed to generate receipt image');
    }
    
    /**
     * Generate A-Pay style receipt HTML
     */
    private function generateReceiptHtml(array $data)
    {
        $amount = number_format($data['amount'], 2);
        $cashback = number_format($data['cashback'], 2);
        $date = date('d M Y H:i');
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0f0f0;
            width: 720px;
            padding: 50px;
        }
        .receipt {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        .header {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            padding: 35px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 38px;
            font-weight: 800;
            color: white;
            letter-spacing: -1px;
        }
        .status-badge {
            background: white;
            color: #16a34a;
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .amount-section {
            text-align: center;
            padding: 50px 40px;
            background: #fafafa;
        }
        .amount {
            font-size: 56px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 8px;
            letter-spacing: -2px;
        }
        .date {
            color: #9ca3af;
            font-size: 15px;
        }
        .details {
            padding: 30px 40px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
            font-size: 15px;
            font-weight: 500;
        }
        .detail-value {
            color: #111827;
            font-size: 15px;
            font-weight: 600;
            text-align: right;
        }
        .cashback-section {
            margin: 20px 40px;
            padding: 25px;
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border-radius: 15px;
            border: 2px solid #22c55e;
            text-align: center;
        }
        .cashback-label {
            color: #15803d;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .cashback-amount {
            color: #166534;
            font-size: 32px;
            font-weight: 800;
        }
        .footer {
            background: #f9fafb;
            padding: 25px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            color: #9ca3af;
            font-size: 12px;
            line-height: 1.7;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="logo">A-Pay</div>
            <div class="status-badge">COMPLETED</div>
        </div>
        
        <div class="amount-section">
            <div class="amount">₦{$amount}</div>
            <div class="date">on {$date}</div>
        </div>
        
        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Transaction Type:</span>
                <span class="detail-value">Airtime Purchase</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Network:</span>
                <span class="detail-value">{$data['network']}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Phone Number:</span>
                <span class="detail-value">{$data['phone']}</span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value">#{$data['transaction_id']}</span>
            </div>
        </div>
HTML;

        if ($data['cashback'] > 0) {
            $html .= <<<HTML
        
        <div class="cashback-section">
            <div class="cashback-label">🎁 Cashback Earned</div>
            <div class="cashback-amount">₦{$cashback}</div>
        </div>
HTML;
        }

        $html .= <<<HTML
        
        <div class="footer">
            <p class="footer-text">Please be aware that this notification does not guarantee</p>
            <p class="footer-text">immediate credit. Internet transactions can encounter</p>
            <p class="footer-text">interruptions, delays, and data inaccuracies.</p>
        </div>
    </div>
</body>
</html>
HTML;

        return $html;
    }
}