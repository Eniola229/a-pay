<?php

//ADMIN
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\UserController;


//User
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Front\DashboardController;
use App\Http\Controllers\Front\TopUpController;
use App\Http\Controllers\Front\WithdrawalController;
use App\Http\Controllers\Front\AirtimePurchaseController;
use App\Http\Controllers\Front\DataPurchaseController;
use App\Http\Controllers\Front\TransactionsController;
use App\Http\Controllers\Front\ElectricityController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Security\DeviceVerificationController;
use App\Http\Controllers\Leaners\RegistrationController;
use App\Http\Controllers\Loan\CreditLimitController;
use App\Http\Controllers\Loan\BorrowAirtimeController;
use App\Http\Controllers\Loan\BorrowDataController;



use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', function () {
    return view('about');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/terms-and-condition', function () {
    return view('terms-and-condition');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/blog', function () {
    return view('blog');
});
//Contact us
Route::post('/contact-us', [ContactInquiryController::class, 'store'])->name('contact.store');

Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handleWebhook'])->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

//2fa
Route::get('/verify-device', [DeviceVerificationController::class, 'show'])->name('verify.device');
Route::post('/verify-device', [DeviceVerificationController::class, 'verify']);

Route::post('/get-payment-amount', [RegistrationController::class, 'getPaymentAmount']);
Route::post('/register-leaners', [RegistrationController::class, 'register'])->name('register-leaners');
Route::get('/register-leaners.com', [RegistrationController::class, 'showForm']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
    //SET PIN
    Route::post('/set-pin', [DashboardController::class, 'setPin'])->name('set.pin');
    Route::post('/reset-pin', [DashboardController::class, 'resetPin'])->name('reset.pin');
    //PROFILE
    Route::get('/profile', [ProfileController::class, 'view'])->name('profile.view');
    Route::post('/update-profile', [ProfileController::class, 'updateProfile'])->name('update.profile');
    Route::post('/update-password', [ProfileController::class, 'updatePassword'])->name('update.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    //TOP UP
    Route::get('/topup', [TopUpController::class, 'index'])->name('topup');
    Route::post('/topup/initialize', [TopUpController::class, 'initialize'])->name('topup.initialize');
    Route::get('/topup/callback', [TopUpController::class, 'callback'])->name('topup.callback');
    // This route will be used by jQuery to fetch the current balance
    Route::get('/balance', [TopUpController::class, 'getBalance'])->name('balance');
    //Withdraw 
    Route::get('/withdraw', [WithdrawalController::class, 'index'])->name('withdraw');
    Route::post('/withdraw/process', [WithdrawalController::class, 'processWithdrawal'])->name('withdraw.process');
    //Airtime
    Route::get('/airtime/buy', [AirtimePurchaseController::class, 'showForm'])->name('airtime.form');
    Route::post('/airtime/buy', [AirtimePurchaseController::class, 'buyAirtime'])->name('buy.airtime');
    Route::get('/recent-purchases', [AirtimePurchaseController::class, 'recentPurchases'])->name('recent.purchases');
    Route::get('/airtime/history', [AirtimePurchaseController::class, 'purchaseHistory'])->name('airtime.history');
    //Borrow Airtime
    Route::get('/borrow/airtime', [BorrowAirtimeController::class, 'showForm'])->name('airtime.borrow');
    Route::post('/borrow/airtime', [BorrowAirtimeController::class, 'buyAirtime'])->name('borrow.airtime');
    //DATA
    Route::get('/data/buy', [DataPurchaseController::class, 'showForm'])->name('data.form');
    Route::get('/data-plans/{networkId}', [DataPurchaseController::class, 'getDataPlans'])->name('data-plans');
    Route::post('/buy-data', [DataPurchaseController::class, 'buyData'])->name('buy.data');
    Route::get('/recent-purchases-data', [DataPurchaseController::class, 'recentPurchases'])->name('recent.purchases-data');
    //Borrow Data
     Route::get('/borrow/data', [BorrowDataController::class, 'showForm'])->name('data.form');
     Route::post('/borrow-data', [BorrowDataController::class, 'buyData'])->name('borrow.data');
    //Pay Electricty
    Route::get('/electricity', [ElectricityController::class, 'showForm'])->name('electricity.form');
    Route::post('/pay-electricity', [ElectricityController::class, 'payElectricity'])->name('pay.electricity');
    //TRANSACTIONS
    Route::get('/transactions', [TransactionsController::class, 'view'])->name('trans');
    //Loan/Borrow
    Route::get('/borrow/credit_limit', [CreditLimitController::class, 'view'])->name('creditlimit');
});

//ADMIN
Route::get('admin/login', [AdminAuthController::class, 'index'])->name('admin-login');
Route::post('post/login', [AdminAuthController::class, 'postLogin'])->name('admin-login.post'); 

Route::get('admin/logout', [AdminAuthController::class, 'logout'])->name('admin-logout');

Route::middleware('auth:admin')->prefix('admin')->group(function () {
 Route::get('/dashboard', [AdminAuthController::class, 'dashboard'])->name('admin-dashboard'); 
 Route::get('/wallet-balance', [AdminAuthController::class, 'getBalance'])->name('wallet.balance');
 Route::get('/transactions', [AdminAuthController::class, 'transactions'])->name('admin-transactions'); 
 Route::get('/complians', [AdminAuthController::class, 'complians'])->name('admin-complians'); 
 Route::get('/users', [UserController::class, 'users'])->name('admin-users'); 
 Route::get('/users/{id}', [UserController::class, 'showUser'])->name('admin.users.show');
 Route::get('/errors', [AdminAuthController::class, 'errors'])->name('admin-errors'); 
 Route::post('/transactions/update', [AdminAuthController::class, 'updateTransaction'])->name('transactions.update');
 Route::get('/users/{id}/edit', [AdminAuthController::class, 'Useredit']);
 Route::post('/users/{id}/update', [AdminAuthController::class, 'Userupdate']);
 Route::get('/notifications', [AdminAuthController::class, 'notification'])->name('admin-notifications'); 
 Route::post('/notifications/store', [AdminAuthController::class, 'Notificationstore'])->name('notifications.store');
 Route::delete('/notifications/{id}', [AdminAuthController::class, 'Notificationdestroy'])->name('notifications.destroy');
 Route::get('/loans', [AdminAuthController::class, 'loans'])->name('admin-loans'); 
});

require __DIR__.'/auth.php';
