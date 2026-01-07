<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log; // Added for logging

class ReceiptGenerator
{
    private $cloudinary;
    private $brandColor = '#10B981';

    public function __construct()
    {
        $cloudinaryUrl = env('CLOUDINARY_URL');
        
        if ($cloudinaryUrl) {
            $parsed = parse_url($cloudinaryUrl);
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => $parsed['host'] ?? env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => $parsed['user'] ?? env('CLOUDINARY_API_KEY'),
                    'api_secret' => $parsed['pass'] ?? env('CLOUDINARY_API_SECRET'),
                ],
            ]);
        } else {
            $this->cloudinary = new Cloudinary([
                'cloud' => [
                    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                    'api_key' => env('CLOUDINARY_API_KEY'),
                    'api_secret' => env('CLOUDINARY_API_SECRET'),
                ],
            ]);
        }
    }

    /**
     * Generate Airtime Purchase Receipt
     */
    public function generateAirtimeReceipt($data)
    {
        $width = 600;
        $height = 1250;
        $img = imagecreatetruecolor($width, $height);
        
        imageantialias($img, true);
        
        $bgGray = imagecolorallocate($img, 249, 250, 251);
        $white = imagecolorallocate($img, 255, 255, 255);
        $cardShadow = imagecolorallocate($img, 229, 231, 235);
        $green = imagecolorallocate($img, 16, 185, 129);
        $darkText = imagecolorallocate($img, 17, 24, 39);
        $mediumText = imagecolorallocate($img, 107, 114, 128);
        $lightText = imagecolorallocate($img, 156, 163, 175);
        $blue = imagecolorallocate($img, 37, 99, 235);
        
        imagefilledrectangle($img, 0, 0, $width, $height, $bgGray);
        
        $cardX = 20;
        $cardY = 20;
        $cardWidth = $width - 40;
        $cardHeight = $height - 40;
        imagefilledrectangle($img, $cardX + 3, $cardY + 3, $cardX + $cardWidth + 3, $cardY + $cardHeight + 3, $cardShadow);
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + $cardHeight, $white);
        
        $leftPad = 50;
        $rightPad = $width - 50;
        $y = 65;
        
        $fontPath = $this->getFontPath();
        $useTTF = $fontPath !== null;
        
        // === HEADER (Size 28, Strong Bold) ===
        if ($useTTF) {
            $this->drawStrongBoldText($img, 28, 0, $leftPad, $y, $green, $fontPath, "A-Pay");
        } else {
            imagestring($img, 5, $leftPad, $y - 15, "A-Pay", $green);
        }
        
        $badgeX = $width - 150;
        $badgeY = $y - 15;
        $badgeWidth = 120;
        $badgeHeight = 36;
        $this->drawRoundedRect($img, $badgeX, $badgeY, $badgeWidth, $badgeHeight, 16, $green);
        
        $badgeText = "COMPLETED";
        
        if ($useTTF) {
            // Badge Size 16
            $bbox = imagettfbbox(16, 0, $fontPath, $badgeText);
            $textWidth = $bbox[2] - $bbox[0];
            $this->drawBoldText($img, 16, 0, $badgeX + ($badgeWidth - $textWidth) / 2, $badgeY + 24, $white, $fontPath, $badgeText);
        } else {
            $textWidth = imagefontwidth(3) * strlen($badgeText);
            imagestring($img, 3, $badgeX + ($badgeWidth - $textWidth) / 2, $badgeY + 9, $badgeText, $white);
        }
        
        $y += 85;
        
        // === AMOUNT (Size 60, SUPER BOLD) ===
        $amountText = "N" . number_format($data['amount'], 2);
        if ($useTTF) {
            $this->drawStrongBoldTextCentered($img, $amountText, $width / 2, $y, 50, $green, $fontPath);
        } else {
            $textWidth = imagefontwidth(5) * strlen($amountText);
            imagestring($img, 5, ($width - $textWidth) / 2, $y - 15, $amountText, $green);
        }
        
        $y += 80;
        
        // === DATE (Size 14) ===
        $dateText = "on " . date('d M Y H:i', strtotime($data['date'] ?? 'now'));
        $this->drawCenteredTextBold($img, $dateText, $width / 2, $y, 14, $lightText, $fontPath, $useTTF);
        
        $y += 70;
        
        // === DETAILS (Size 18, Lighter Bold) ===
        $details = [
            ['Sender:', strtoupper($data['customer_name'])],
            ['Recipient:', $data['phone']],
            ['Network/Provider:', strtoupper($data['network'])],
            ['Type:', 'Airtime Purchase'],
            ['Reference:', $this->truncate($data['reference'], 20)],
            // ['Transaction ID:', $this->truncate($data['account_number'], 18)],
        ];
        
        foreach ($details as $detail) {
            // Details Size 18, Standard Bold (Lighter)
            $this->drawLeftRightTextBold($img, $detail[0], $detail[1], $leftPad, $rightPad, $y, 18, $lightText, $darkText, $fontPath, $useTTF);
            $y += 55; 
        }
        
        if (isset($data['cashback']) && $data['cashback'] > 0) {
            $y += 10;
            $cashbackAmount = "N" . number_format($data['cashback'], 2);
            $this->drawLeftRightTextBold($img, 'Cashback:', $cashbackAmount, $leftPad, $rightPad, $y, 18, $lightText, $green, $fontPath, $useTTF);
            $y += 55;
        }
        
        $y += 30;
        $this->drawZigzagLine($img, 35, $y, $width - 70, $green);
        $y += 50;
        
        // === PROMO (Size 15) ===
        $bannerY = $y;
        $bannerHeight = 90;
        $this->drawRoundedRect($img, 45, $bannerY, $width - 90, $bannerHeight, 12, $green);
        $this->drawCenteredTextBold($img, "Enjoy cashback rewards!", $width / 2, $bannerY + 33, 15, $white, $fontPath, $useTTF);
        $this->drawCenteredTextBold($img, "Keep using A-Pay for more benefits", $width / 2, $bannerY + 60, 11, $white, $fontPath, $useTTF);
        
        $y += $bannerHeight + 60;
        
        // === FOOTER (Size 12) ===
        $footerLines = [
            // ["A-Pay is powered by CBN-Licensed partners and protected by NDIC.", 12],
            ["Buy airtime, pay bills - the smart and easy way", 13],
            ["with A-Pay all inside WhatsApp.", 13],
            // ["AfricGEM International Company Limited", 12],
            ["", 12],
            ["Start paying smarter at www.africicl.com.ng/a-pay", 14]
        ];
        
        foreach ($footerLines as $line) {
            if ($line[0] === "") {
                $y += $line[1];
                continue;
            }
            $color = $line[1] > 13 ? $mediumText : $lightText;
            $this->drawCenteredTextBold($img, $line[0], $width / 2, $y, $line[1], $color, $fontPath, $useTTF);
            $y += 28;
        }
        
        return $this->saveAndUpload($img, $data['reference'], 'airtime');
    }

    /**
     * Generate Data Purchase Receipt
     */
    public function generateDataReceipt($data)
    {
        $width = 600;
        $height = 1300; 
        $img = imagecreatetruecolor($width, $height);
        
        imageantialias($img, true);
        
        $bgGray = imagecolorallocate($img, 249, 250, 251);
        $white = imagecolorallocate($img, 255, 255, 255);
        $cardShadow = imagecolorallocate($img, 229, 231, 235);
        $green = imagecolorallocate($img, 16, 185, 129);
        $darkText = imagecolorallocate($img, 17, 24, 39);
        $mediumText = imagecolorallocate($img, 107, 114, 128);
        $lightText = imagecolorallocate($img, 156, 163, 175);
        $blue = imagecolorallocate($img, 37, 99, 235);
        
        imagefilledrectangle($img, 0, 0, $width, $height, $bgGray);
        
        $cardX = 20;
        $cardY = 20;
        $cardWidth = $width - 40;
        $cardHeight = $height - 40;
        imagefilledrectangle($img, $cardX + 3, $cardY + 3, $cardX + $cardWidth + 3, $cardY + $cardHeight + 3, $cardShadow);
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + $cardHeight, $white);
        
        $leftPad = 50;
        $rightPad = $width - 50;
        $y = 65;
        
        $fontPath = $this->getFontPath();
        $useTTF = $fontPath !== null;
        
        // Header
        if ($useTTF) {
            $this->drawStrongBoldText($img, 28, 0, $leftPad, $y, $green, $fontPath, "A-Pay");
        } else {
            imagestring($img, 5, $leftPad, $y - 15, "A-Pay", $green);
        }
        
        $badgeX = $width - 150;
        $badgeY = $y - 15;
        $this->drawRoundedRect($img, $badgeX, $badgeY, 120, 36, 16, $green);
        
        if ($useTTF) {
            $bbox = imagettfbbox(16, 0, $fontPath, "COMPLETED");
            $textWidth = $bbox[2] - $bbox[0];
            $this->drawBoldText($img, 16, 0, $badgeX + (120 - $textWidth) / 2, $badgeY + 24, $white, $fontPath, "COMPLETED");
        } else {
            $textWidth = imagefontwidth(3) * 9;
            imagestring($img, 3, $badgeX + (120 - $textWidth) / 2, $badgeY + 9, "COMPLETED", $white);
        }
        
        $y += 85;
        
        // Amount
        $amountText = "N" . number_format($data['amount'], 2);
        if ($useTTF) {
            $this->drawStrongBoldTextCentered($img, $amountText, $width / 2, $y, 50, $green, $fontPath);
        } else {
            $textWidth = imagefontwidth(5) * strlen($amountText);
            imagestring($img, 5, ($width - $textWidth) / 2, $y - 15, $amountText, $green);
        }
        
        $y += 80;
        
        $dateText = "on " . date('d M Y H:i', strtotime($data['date'] ?? 'now'));
        $this->drawCenteredTextBold($img, $dateText, $width / 2, $y, 14, $lightText, $fontPath, $useTTF);
        
        $y += 70;
        
        // Details
        $details = [
            ['Sender:', strtoupper($data['customer_name'])],
            ['Phone Number:', $data['phone']],
            ['Network:', strtoupper($data['network'])],
            ['Data Plan:', $data['plan']],
            ['Type:', 'Data Subscription'],
            ['Reference:', $this->truncate($data['reference'], 20)],
            // ['Transaction ID:', $this->truncate($data['account_number'], 18)],
        ];
        
        foreach ($details as $detail) {
            $this->drawLeftRightTextBold($img, $detail[0], $detail[1], $leftPad, $rightPad, $y, 18, $lightText, $darkText, $fontPath, $useTTF);
            $y += 55;
        }
        
        if (isset($data['cashback']) && $data['cashback'] > 0) {
            $y += 10;
            $cashbackAmount = "N" . number_format($data['cashback'], 2);
            $this->drawLeftRightTextBold($img, 'Cashback:', $cashbackAmount, $leftPad, $rightPad, $y, 18, $lightText, $green, $fontPath, $useTTF);
            $y += 55;
        }
        
        $y += 30;
        $this->drawZigzagLine($img, 35, $y, $width - 70, $green);
        $y += 50;
        
        $this->drawRoundedRect($img, 45, $y, $width - 90, 90, 12, $green);
        $this->drawCenteredTextBold($img, "Enjoy cashback rewards!", $width / 2, $y + 33, 15, $white, $fontPath, $useTTF);
        $this->drawCenteredTextBold($img, "Keep using A-Pay for more benefits", $width / 2, $y + 60, 11, $white, $fontPath, $useTTF);
        
        $y += 140;
        
        $footerLines = [
            // ["A-Pay is powered by CBN-Licensed partners and protected by NDIC.", 12],
            ["Buy airtime, pay bills - the smart and easy way", 13],
            ["with A-Pay all inside WhatsApp.", 13],
            // ["AfricGEM International Company Limited", 12],
            ["", 12],
            ["Start paying smarter at www.africicl.com.ng/a-pay", 14]
        ];
        
        foreach ($footerLines as $line) {
            if ($line[0] === "") {
                $y += $line[1];
                continue;
            }
            $color = $line[1] > 13 ? $mediumText : $lightText;
            $this->drawCenteredTextBold($img, $line[0], $width / 2, $y, $line[1], $color, $fontPath, $useTTF);
            $y += 28;
        }
        
        return $this->saveAndUpload($img, $data['reference'], 'data');
    }

    /**
     * Generate Electricity Purchase Receipt
     */
    public function generateElectricityReceipt($data)
    {
        $width = 600;
        $height = 1350;
        $img = imagecreatetruecolor($width, $height);
        
        imageantialias($img, true);
        
        $bgGray = imagecolorallocate($img, 249, 250, 251);
        $white = imagecolorallocate($img, 255, 255, 255);
        $cardShadow = imagecolorallocate($img, 229, 231, 235);
        $green = imagecolorallocate($img, 16, 185, 129);
        $darkText = imagecolorallocate($img, 17, 24, 39);
        $mediumText = imagecolorallocate($img, 107, 114, 128);
        $lightText = imagecolorallocate($img, 156, 163, 175);
        $blue = imagecolorallocate($img, 37, 99, 235);
        
        imagefilledrectangle($img, 0, 0, $width, $height, $bgGray);
        
        $cardX = 20;
        $cardY = 20;
        $cardWidth = $width - 40;
        $cardHeight = $height - 40;
        imagefilledrectangle($img, $cardX + 3, $cardY + 3, $cardX + $cardWidth + 3, $cardY + $cardHeight + 3, $cardShadow);
        imagefilledrectangle($img, $cardX, $cardY, $cardX + $cardWidth, $cardY + $cardHeight, $white);
        
        $leftPad = 50;
        $rightPad = $width - 50;
        $y = 65;
        
        $fontPath = $this->getFontPath();
        $useTTF = $fontPath !== null;
        
        // Header
        if ($useTTF) {
            $this->drawStrongBoldText($img, 28, 0, $leftPad, $y, $green, $fontPath, "A-Pay");
        } else {
            imagestring($img, 5, $leftPad, $y - 15, "A-Pay", $green);
        }
        
        $badgeX = $width - 150;
        $badgeY = $y - 15;
        $this->drawRoundedRect($img, $badgeX, $badgeY, 120, 36, 16, $green);
        
        if ($useTTF) {
            $bbox = imagettfbbox(16, 0, $fontPath, "COMPLETED");
            $textWidth = $bbox[2] - $bbox[0];
            $this->drawBoldText($img, 16, 0, $badgeX + (120 - $textWidth) / 2, $badgeY + 24, $white, $fontPath, "COMPLETED");
        } else {
            $textWidth = imagefontwidth(3) * 9;
            imagestring($img, 3, $badgeX + (120 - $textWidth) / 2, $badgeY + 9, "COMPLETED", $white);
        }
        
        $y += 85;
        
        // Amount
        $amountText = "N" . number_format($data['amount'], 2);
        if ($useTTF) {
            $this->drawStrongBoldTextCentered($img, $amountText, $width / 2, $y, 50, $green, $fontPath);
        } else {
            $textWidth = imagefontwidth(5) * strlen($amountText);
            imagestring($img, 5, ($width - $textWidth) / 2, $y - 15, $amountText, $green);
        }
        
        $y += 80;
        
        $dateText = "on " . date('d M Y H:i', strtotime($data['date'] ?? 'now'));
        $this->drawCenteredTextBold($img, $dateText, $width / 2, $y, 14, $lightText, $fontPath, $useTTF);
        
        $y += 70;
        
        // Details
        $details = [
            ['Sender:', strtoupper($data['customer_name'])],
            ['Meter Number:', $data['meter_number']],
            ['Provider:', ucfirst($data['provider'])],
            ['Type:', 'Electricity Bill'],
            ['Address:', $data['customer_address']],
            ['Account Name:', $data['customer_name_m']],
            ['Reference:', $this->truncate($data['reference'], 20)],
            // ['Transaction ID:', $this->truncate($data['account_number'], 18)],
        ];
        
        foreach ($details as $detail) {
            $this->drawLeftRightTextBold($img, $detail[0], $detail[1], $leftPad, $rightPad, $y, 18, $lightText, $darkText, $fontPath, $useTTF);
            $y += 55;
        }

        $y += 15;
        
        // === TOKEN AND UNITS SECTION ===
        // Token Size 20
        $this->drawLeftRightTextBold($img, 'Token:', $data['token'], $leftPad, $rightPad, $y, 20, $lightText, $green, $fontPath, $useTTF);
        $y += 55;
        
        $this->drawLeftRightTextBold($img, 'Units:', $data['units'], $leftPad, $rightPad, $y, 18, $lightText, $darkText, $fontPath, $useTTF);
        $y += 55;
        
        if (isset($data['cashback']) && $data['cashback'] > 0) {
            $y += 10;
            $cashbackAmount = "N" . number_format($data['cashback'], 2);
            $this->drawLeftRightTextBold($img, 'Cashback:', $cashbackAmount, $leftPad, $rightPad, $y, 18, $lightText, $green, $fontPath, $useTTF);
            $y += 55;
        }
        
        $y += 30;
        $this->drawZigzagLine($img, 35, $y, $width - 70, $green);
        $y += 50;
        
        $this->drawRoundedRect($img, 45, $y, $width - 90, 90, 12, $green);
        $this->drawCenteredTextBold($img, "Electricity payment successful!", $width / 2, $y + 33, 15, $white, $fontPath, $useTTF);
        $this->drawCenteredTextBold($img, "Keep using A-Pay for more benefits", $width / 2, $y + 60, 11, $white, $fontPath, $useTTF);
        
        $y += 140;
        
        $footerLines = [
            // ["A-Pay is powered by CBN-Licensed partners and protected by NDIC.", 12],
            ["Buy airtime, pay bills - the smart and easy way", 13],
            ["with A-Pay all inside WhatsApp.", 13],
            // ["AfricGEM International Company Limited", 12],
            ["", 12],
            ["Start paying smarter at www.africicl.com.ng/a-pay", 14]
        ];
        
        foreach ($footerLines as $line) {
            if ($line[0] === "") {
                $y += $line[1];
                continue;
            }
            $color = $line[1] > 13 ? $mediumText : $lightText;
            $this->drawCenteredTextBold($img, $line[0], $width / 2, $y, $line[1], $color, $fontPath, $useTTF);
            $y += 28;
        }
        
        return $this->saveAndUpload($img, $data['reference'], 'electricity');
    }


    /**
     * Generate Advanced Transaction Analysis Report
     */
    /**
     * Generate Advanced Transaction Analysis Report
     */
    public function generateHistoryReport($stats)
    {
        $width = 600;
        $height = 1100; // Increased height to fit new sections
        $img = imagecreatetruecolor($width, $height);
        
        imageantialias($img, true);
        
        // --- COLOR PALETTE ---
        $bgCanvas = imagecolorallocate($img, 243, 244, 246);
        $cardWhite = imagecolorallocate($img, 255, 255, 255);
        $brandGreen = imagecolorallocate($img, 16, 185, 129);
        $brandDarkGreen = imagecolorallocate($img, 6, 78, 59);
        $brandBlue = imagecolorallocate($img, 59, 130, 246); // Blue for Topups
        $brandRed = imagecolorallocate($img, 239, 68, 68);   // Red for Debits
        $textMain = imagecolorallocate($img, 31, 41, 55);
        $textMuted = imagecolorallocate($img, 107, 114, 128);
        $barBg = imagecolorallocate($img, 229, 231, 235);

        imagefilledrectangle($img, 0, 0, $width, $height, $bgCanvas);
        
        // Draw Main Card
        $this->drawCardBackground($img, 20, 20, 560, 1060, $cardWhite);

        $leftPad = 50;
        $y = 80;
        
        $fontPath = $this->getFontPath();
        $useTTF = $fontPath !== null;

        // === 1. HEADER ===
        $this->drawRoundedRect($img, 50, 40, 500, 60, 12, $brandDarkGreen);
        
        if ($useTTF) {
            $this->drawCenteredTextBold($img, "TRANSACTION ANALYSIS", 300, 76, 18, $cardWhite, $fontPath, $useTTF);
        }

        $y = 130;

        // === 2. HERO STATS (Monthly Spend) ===
        $label = "Total Spend (This Month)";
        if ($useTTF) {
            $this->drawText($img, 14, $leftPad, $y, $textMuted, $fontPath, $label);
        }
        $y += 60;

        $monthlyAmount = "₦" . number_format($stats['month_total'], 2);
        if ($useTTF) {
            $this->drawStrongBoldText($img, 42, 0, $leftPad, $y, $brandGreen, $fontPath, $monthlyAmount);
        }
        $y += 40;

        $monthName = date('F Y');
        $metaText = "$monthName • {$stats['month_count']} Transactions";
        if ($useTTF) {
            $this->drawText($img, 13, $leftPad, $y, $textMuted, $fontPath, $metaText);
        }
        $y += 50;

        // === 3. FINANCIAL FLOW (Credit vs Debit) ===
        $this->drawDivider($img, 50, $y, 500, $barBg);
        $y += 40;
        
        if ($useTTF) {
            $this->drawBoldText($img, 16, 0, $leftPad, $y, $textMain, $fontPath, "Financial Flow");
        }
        $y += 35;

        // Credit (In)
        $creditAmt = "₦" . number_format($stats['month_credit'], 2);
        if ($useTTF) {
            $this->drawLeftRightTextBold($img, "Money In (Credit)", $creditAmt, $leftPad, 550, $y, 14, $textMuted, $brandGreen, $fontPath, $useTTF);
        }
        $y += 30;

        // Debit (Out)
        $debitAmt = "₦" . number_format($stats['month_debit'], 2);
        if ($useTTF) {
            $this->drawLeftRightTextBold($img, "Money Out (Debit)", $debitAmt, $leftPad, 550, $y, 14, $textMuted, $brandRed, $fontPath, $useTTF);
        }
        $y += 50;

        // === 4. MONTHLY EXTRAS (Cashback & Topup) ===
        $this->drawDivider($img, 50, $y, 500, $barBg);
        $y += 40;

        if ($useTTF) {
            $this->drawBoldText($img, 16, 0, $leftPad, $y, $textMain, $fontPath, "Monthly Extras");
        }
        $y += 35;

        // Cashback
        $cashbackAmt = "₦" . number_format($stats['month_cashback'], 2);
        if ($useTTF) {
            $this->drawLeftRightTextBold($img, "Cashbacks Earned", $cashbackAmt, $leftPad, 550, $y, 14, $textMuted, $brandGreen, $fontPath, $useTTF);
        }
        $y += 30;

        // Wallet Topup
        $topupAmt = "₦" . number_format($stats['month_topup'], 2);
        if ($useTTF) {
            $this->drawLeftRightTextBold($img, "Wallet Top-ups", $topupAmt, $leftPad, 550, $y, 14, $textMuted, $brandBlue, $fontPath, $useTTF);
        }

        $y += 50;

        // === 5. SPENDING BREAKDOWN (Visual Bars) ===
        $this->drawDivider($img, 50, $y, 500, $barBg);
        $y += 40;
        
        if ($useTTF) {
            $this->drawBoldText($img, 16, 0, $leftPad, $y, $textMain, $fontPath, "Spending Breakdown");
        }
        $y += 35;

        $categories = [
            ['Airtime', $stats['airtime_total']],
            ['Data', $stats['data_total']],
            ['Electricity', $stats['electricity_total']],
        ];
        
        $maxSpend = max($stats['airtime_total'], $stats['data_total'], $stats['electricity_total']);
        if ($maxSpend == 0) $maxSpend = 1;

        foreach ($categories as $cat) {
            $label = $cat[0];
            $amount = $cat[1];
            $formattedAmount = "₦" . number_format($amount, 2);
            
            if ($useTTF) {
                $this->drawLeftRightTextBold($img, $label, $formattedAmount, $leftPad, 550, $y, 14, $textMain, $textMain, $fontPath, $useTTF);
            }
            $y += 20;

            $barWidth = 400;
            $this->drawRoundedRect($img, $leftPad, $y, $barWidth, 8, 4, $barBg);

            if ($amount > 0) {
                $fillWidth = ($amount / $maxSpend) * $barWidth;
                $this->drawRoundedRect($img, $leftPad, $y, $fillWidth, 8, 4, $brandGreen);
            }
            $y += 35;
        }

        $y += 30;
        $this->drawDivider($img, 50, $y, 500, $barBg);
        $y += 40;

        // === 6. YEARLY SNAPSHOT (Side by Side) ===
        $this->drawDivider($img, 50, $y, 500, $barBg);
        $y += 40;

        if ($useTTF) {
            $this->drawBoldText($img, 16, 0, $leftPad, $y, $textMain, $fontPath, "Yearly Snapshot");
        }
        $y += 35;

        // Labels Row
        if ($useTTF) {
            $this->drawLeftRightTextBold($img, "Money Out (Debit)", "Money In (Credit)", $leftPad, 550, $y, 14, $textMuted, $textMuted, $fontPath, $useTTF);
        }
        $y += 35;

        // Amounts Row
        $yearDebit = "₦" . number_format($stats['year_debit'], 2);
        $yearCredit = "₦" . number_format($stats['year_credit'], 2);
        
        if ($useTTF) {
            // Left: Debit (Red), Right: Credit (Green)
            $this->drawLeftRightTextBold($img, $yearDebit, $yearCredit, $leftPad, 550, $y, 28, $brandRed, $brandGreen, $fontPath, $useTTF);
        }
        
        $y += 100;

        // Footer
        if ($useTTF) {
            $footer = "Analysis generated on " . date('d M Y, H:i');
            $this->drawCenteredTextBold($img, $footer, 300, $y, 10, $textMuted, $fontPath, $useTTF);
        }

        return $this->saveAndUpload($img, 'analysis_' . time(), 'history');
    }

    // Helper to draw a card with shadow
    private function drawCardBackground($img, $x, $y, $width, $height, $color)
    {
        // Shadow
        $shadow = imagecolorallocate($img, 200, 200, 200);
        imagefilledrectangle($img, $x + 4, $y + 4, $x + $width, $y + $height, $shadow);
        // Card
        imagefilledrectangle($img, $x, $y, $x + $width, $y + $height, $color);
    }
    
    // Simple text wrapper (Non-bold, 1 color)
    private function drawText($img, $size, $x, $y, $color, $fontPath, $text)
    {
        imagettftext($img, $size, 0, $x, $y, $color, $fontPath, $text);
    }
    // === HELPER METHODS ===
    
        private function drawDivider($img, $x, $y, $width, $color)
    {
        imagesetthickness($img, 1);
        imageline($img, $x, $y, $x + $width, $y, $color);
        imagesetthickness($img, 1);
    }

    // Strong Bold for Header and Amount (2px offset shadow)
    private function drawStrongBoldText($img, $size, $angle, $x, $y, $color, $fontPath, $text)
    {
        imagettftext($img, $size, $angle, $x + 2, $y + 2, $color, $fontPath, $text);
        imagettftext($img, $size, $angle, $x, $y, $color, $fontPath, $text);
    }

    private function drawStrongBoldTextCentered($img, $text, $x, $y, $size, $color, $fontPath)
    {
        $bbox = imagettfbbox($size, 0, $fontPath, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $startX = $x - $textWidth / 2;
        
        // 2px shadow for super boldness
        imagettftext($img, $size, 0, $startX + 2, $y + 2, $color, $fontPath, $text);
        imagettftext($img, $size, 0, $startX, $y, $color, $fontPath, $text);
    }

    // Standard Bold (1px shadow) for general text
    private function drawBoldText($img, $size, $angle, $x, $y, $color, $fontPath, $text)
    {
        imagettftext($img, $size, $angle, $x + 1, $y + 1, $color, $fontPath, $text);
        imagettftext($img, $size, $angle, $x, $y, $color, $fontPath, $text);
    }
    
    private function drawRoundedRect($img, $x, $y, $width, $height, $radius, $color)
    {
        imagefilledrectangle($img, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
        imagefilledrectangle($img, $x, $y + $radius, $x + $width, $y + $height - $radius, $color);
        imagefilledellipse($img, $x + $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $width - $radius, $y + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($img, $x + $width - $radius, $y + $height - $radius, $radius * 2, $radius * 2, $color);
    }
    
    private function drawZigzagLine($img, $x, $y, $width, $color)
    {
        $zigWidth = 18;
        $zigHeight = 8;
        $steps = intval($width / $zigWidth);
        
        imagesetthickness($img, 2);
        for ($i = 0; $i < $steps; $i++) {
            $x1 = $x + ($i * $zigWidth);
            $y1 = $i % 2 == 0 ? $y : $y + $zigHeight;
            $x2 = $x + (($i + 1) * $zigWidth);
            $y2 = $i % 2 == 0 ? $y + $zigHeight : $y;
            imageline($img, $x1, $y1, $x2, $y2, $color);
        }
        imagesetthickness($img, 1);
    }
    
    // General Text Helper (Applies Bold effect)
    private function drawCenteredTextBold($img, $text, $x, $y, $size, $color, $fontPath, $useTTF)
    {
        if ($useTTF) {
            $bbox = imagettfbbox($size, 0, $fontPath, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $startX = $x - $textWidth / 2;
            
            imagettftext($img, $size, 0, $startX + 1, $y + 1, $color, $fontPath, $text);
            imagettftext($img, $size, 0, $startX, $y, $color, $fontPath, $text);
        } else {
            $gdSize = min(5, max(1, intval($size / 4)));
            $textWidth = imagefontwidth($gdSize) * strlen($text);
            imagestring($img, $gdSize, $x - $textWidth / 2, $y - imagefontheight($gdSize) / 2, $text, $color);
        }
    }
    
    // Details Helper (Applies Bold effect)
    private function drawLeftRightTextBold($img, $leftText, $rightText, $leftX, $rightX, $y, $size, $leftColor, $rightColor, $fontPath, $useTTF)
    {
        if ($useTTF) {
            // Left Text Bold
            imagettftext($img, $size, 0, $leftX + 1, $y + 1, $leftColor, $fontPath, $leftText);
            imagettftext($img, $size, 0, $leftX, $y, $leftColor, $fontPath, $leftText);
            
            // Right Text Bold
            $bbox = imagettfbbox($size, 0, $fontPath, $rightText);
            $textWidth = $bbox[2] - $bbox[0];
            $rightStartX = $rightX - $textWidth;
            
            imagettftext($img, $size, 0, $rightStartX + 1, $y + 1, $rightColor, $fontPath, $rightText);
            imagettftext($img, $size, 0, $rightStartX, $y, $rightColor, $fontPath, $rightText);
        } else {
            $gdSize = 3;
            imagestring($img, $gdSize, $leftX, $y - 8, $leftText, $leftColor);
            $textWidth = imagefontwidth($gdSize) * strlen($rightText);
            imagestring($img, $gdSize, $rightX - $textWidth, $y - 8, $rightText, $rightColor);
        }
    }
    
    private function getFontPath()
    {
        // 1. Check Storage folders (Upload your font here: storage/fonts/Arial.ttf)
        $possibleFonts = [
            storage_path('fonts/Arial.ttf'),
            storage_path('fonts/arial.ttf'),
            storage_path('app/fonts/Arial.ttf'), // Alternative storage path
            public_path('fonts/Arial.ttf'), // If you put it in public
        ];
        
        foreach ($possibleFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        // 2. Check System Fonts (Production Fallbacks)
        $systemFonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf', 
            'C:\Windows\Fonts\arial.ttf',
            'C:\Windows\Fonts\Arial.ttf',
        ];

        foreach ($systemFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }
        
        // Log if no font is found (Helps debugging)
        // \Log::warning('ReceiptGenerator: No TTF font found. Falling back to default font.');
        
        return null;
    }
    
    private function truncate($text, $length)
    {
        return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
    }
    
    private function saveAndUpload($img, $reference, $type)
    {
        $tempPath = storage_path('app/temp/receipt_' . time() . '.png');
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        
        imagepng($img, $tempPath, 9);
        imagedestroy($img);
        
        $uploadResult = $this->cloudinary->uploadApi()->upload($tempPath, [
            'folder' => 'apay/receipts/' . $type,
            'public_id' => 'receipt_' . $reference,
            'overwrite' => true,
            'resource_type' => 'image'
        ]);
        
        unlink($tempPath);
        
        return $uploadResult['secure_url'];
    }
}