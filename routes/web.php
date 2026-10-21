<?php

use App\Http\Controllers\Admin\AffiliateController as AdminAffiliateController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Affiliate\AuthController as AffiliateAuthController;
use App\Http\Controllers\Affiliate\PanelController as AffiliatePanelController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PricingSettingsController;
use App\Http\Controllers\Admin\RdmInstanceController;
use App\Http\Controllers\Admin\VpsServerController;
use App\Http\Controllers\Api\DeployController as ApiDeployController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\SubdomainController;
use App\Http\Controllers\CheckoutPageController;
use App\Http\Controllers\DeployPageController;
use App\Http\Controllers\DeployPaymentController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\AndidevWebhookController;
use App\Http\Controllers\TripayWebhookController;
use App\Http\Controllers\XenditWebhookController;
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\User\PageController as UserPageController;
use App\Http\Controllers\User\PasswordResetController;
use App\Http\Controllers\User\PasswordController as UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::name('pages.')->group(function () {
    Route::view('/tentang-kami', 'pages.about')->name('about');
    Route::view('/syarat-ketentuan', 'pages.terms')->name('terms');
    Route::view('/kebijakan-privasi', 'pages.privacy')->name('privacy');
    Route::view('/kebijakan-pengembalian-dana', 'pages.refund')->name('refund');
    Route::view('/kontak', 'pages.contact')->name('contact');
});

Route::get('/checkout', CheckoutPageController::class)->name('checkout');
Route::get('/deploy', [DeployPageController::class, 'index'])->name('deploy');
Route::get('/deploy/pembayaran/berhasil', [DeployPaymentController::class, 'success'])->name('deploy.payment.success');

Route::prefix('perpanjang')->name('renewal.')->group(function () {
    Route::get('/{subdomain}', [RenewalController::class, 'show'])->name('show');
    Route::post('/{subdomain}', [RenewalController::class, 'prepareCheckout'])->name('prepare');
    Route::get('/{subdomain}/checkout', [RenewalController::class, 'checkoutForm'])->name('checkout');
    Route::post('/{subdomain}/checkout', [RenewalController::class, 'processPayment'])->middleware('throttle:10,1')->name('pay');
    Route::get('/{subdomain}/berhasil', [RenewalController::class, 'success'])->name('success');
    Route::get('/{subdomain}/bayar', [RenewalController::class, 'pay'])->name('pay');
    Route::get('/{subdomain}/bayar/status', [RenewalController::class, 'payStatus'])->name('pay.status');
    Route::get('/{subdomain}/qris', [RenewalController::class, 'qris'])->name('qris');
    Route::get('/{subdomain}/qris/status', [RenewalController::class, 'qrisStatus'])->name('qris.status');
});

Route::post('/webhooks/xendit/invoice', [XenditWebhookController::class, 'invoice'])
    ->name('webhooks.xendit.invoice');
Route::post('/webhooks/xendit/qr-code', [XenditWebhookController::class, 'qrCode'])
    ->name('webhooks.xendit.qr-code');
Route::post('/webhooks/xendit/payment-request', [XenditWebhookController::class, 'paymentRequest'])
    ->name('webhooks.xendit.payment-request');
Route::post('/webhooks/andidev-pay', [AndidevWebhookController::class, 'handle'])
    ->name('webhooks.andidev');
Route::post('/webhooks/tripay/callback', [TripayWebhookController::class, 'handle'])
    ->name('webhooks.tripay');

Route::prefix('api')->group(function () {
    Route::get('/subdomain/check', [SubdomainController::class, 'check']);
    Route::get('/email/check', [EmailController::class, 'check']);

    Route::post('/deploy/prepare', [ApiDeployController::class, 'prepare'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::post('/deploy/checkout', [ApiDeployController::class, 'checkout'])->middleware('throttle:10,1');
        Route::get('/deploy/payment/{externalId}', [ApiDeployController::class, 'paymentStatus']);
        Route::get('/deploy/{instance}', [ApiDeployController::class, 'status'])->whereNumber('instance');
        Route::post('/deploy/{instance}/retry', [ApiDeployController::class, 'retry'])->whereNumber('instance');
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [UserAuthController::class, 'showLogin'])->name('user.login');
    Route::post('/login', [UserAuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('user.password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('user.password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('user.password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('user.password.update');
});

Route::post('/logout', [UserAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('user.logout');

Route::middleware('auth')->group(function () {
    Route::prefix('dashboard')->name('user.')->middleware('subscription.active')->group(function () {
        Route::get('/', [UserPageController::class, 'dashboard'])->name('dashboard');
        Route::get('/rdm', [UserPageController::class, 'rdm'])->name('rdm');
        Route::post('/rdm/{rdmInstance}/retry', [UserPageController::class, 'retryDeploy'])->name('rdm.retry');
        Route::get('/deploy', [DeployPageController::class, 'panel'])->name('deploy');
        Route::get('/domain', [UserPageController::class, 'domain'])->name('domain');
        Route::get('/backup', [UserPageController::class, 'backup'])->name('backup');
        Route::get('/monitoring', [UserPageController::class, 'monitoring'])->name('monitoring');
        Route::get('/billing', [UserPageController::class, 'billing'])->name('billing');
        Route::get('/support', [UserPageController::class, 'support'])->name('support');
        Route::get('/profile', [UserPageController::class, 'profile'])->name('profile');
        Route::post('/profile/password', [UserPasswordController::class, 'update'])->name('profile.password');
        Route::post('/profile/password/dismiss', [UserPasswordController::class, 'dismissPrompt'])->name('profile.password.dismiss');
        Route::get('/langganan-habis', [UserPageController::class, 'subscriptionExpired'])->name('subscription.expired');
    });
});

Route::prefix('affiliate')->name('affiliate.')->group(function () {
    Route::get('/', [AffiliateAuthController::class, 'program'])->name('program');
    Route::get('/daftar', [AffiliateAuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AffiliateAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/masuk', [AffiliateAuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AffiliateAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::post('/keluar', [AffiliateAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:affiliate')->group(function () {
        Route::get('/verifikasi-email', [AffiliateAuthController::class, 'showVerification'])->name('verification');
        Route::post('/verifikasi-email', [AffiliateAuthController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
        Route::post('/verifikasi-email/kirim-ulang', [AffiliateAuthController::class, 'resendVerification'])->middleware('throttle:3,1')->name('verification.resend');
        Route::post('/verifikasi-email/ganti-email', [AffiliateAuthController::class, 'changeVerificationEmail'])->middleware('throttle:3,10')->name('verification.email');

        Route::middleware('affiliate.verified')->group(function () {
            Route::get('/dashboard', [AffiliatePanelController::class, 'dashboard'])->name('dashboard');
            Route::get('/profil', [AffiliatePanelController::class, 'profile'])->name('profile');
            Route::post('/profil', [AffiliatePanelController::class, 'updateProfile'])->name('profile.update');
            Route::post('/profil/password', [AffiliatePanelController::class, 'updatePassword'])->name('profile.password');
            Route::post('/profil/rekening', [AffiliatePanelController::class, 'updateBank'])->middleware('throttle:10,1')->name('profile.bank');
            Route::get('/profil/dokumen/{type}', [AffiliatePanelController::class, 'document'])->whereIn('type', ['ktp', 'bank_book'])->name('document');

            Route::middleware('affiliate.active')->group(function () {
                Route::get('/referral', [AffiliatePanelController::class, 'referrals'])->name('referrals');
                Route::get('/komisi', [AffiliatePanelController::class, 'commissions'])->name('commissions');
                Route::get('/pencairan', [AffiliatePanelController::class, 'payouts'])->name('payouts');
                Route::post('/pencairan', [AffiliatePanelController::class, 'requestPayout'])->middleware('throttle:5,1')->name('payouts.request');
            });
        });
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [PageController::class, 'dashboard'])->name('dashboard');
        Route::get('/pelanggan', [PageController::class, 'pelanggan'])->name('pelanggan');
        Route::post('/pelanggan', [CustomerController::class, 'store'])->name('pelanggan.store');
        Route::post('/pelanggan/{user}/reset-password', [CustomerController::class, 'resetPassword'])->name('pelanggan.reset-password');
        Route::delete('/pelanggan/{user}', [CustomerController::class, 'destroy'])->name('pelanggan.destroy');
        Route::get('/rdm', [PageController::class, 'rdm'])->name('rdm');
        Route::post('/rdm/{rdmInstance}/renew', [RdmInstanceController::class, 'renew'])->name('rdm.renew');
        Route::post('/rdm/{rdmInstance}/deactivate', [RdmInstanceController::class, 'deactivate'])->name('rdm.deactivate');
        Route::delete('/rdm/{rdmInstance}', [RdmInstanceController::class, 'destroy'])->name('rdm.destroy');
        Route::get('/rdm/{rdmInstance}/destroy-status', [RdmInstanceController::class, 'destroyStatus'])->name('rdm.destroy-status');
        Route::post('/rdm/{rdmInstance}/retry', [RdmInstanceController::class, 'retry'])->name('rdm.retry');
        Route::get('/rdm/{rdmInstance}/deploy-status', [RdmInstanceController::class, 'deployStatus'])->name('rdm.deploy-status');
        Route::get('/vps', [PageController::class, 'vps'])->name('vps');
        Route::post('/vps', [VpsServerController::class, 'store'])->name('vps.store');
        Route::post('/vps/{vpsServer}/ssh', [VpsServerController::class, 'updateSsh'])->name('vps.ssh');
        Route::post('/vps/{vpsServer}/generate-key', [VpsServerController::class, 'generateKey'])->name('vps.generate-key');
        Route::post('/vps/{vpsServer}/detect-specs', [VpsServerController::class, 'detectSpecs'])->name('vps.detect-specs');
        Route::post('/vps/{vpsServer}/sync', [VpsServerController::class, 'syncDeploy'])->name('vps.sync');
        Route::post('/vps/{vpsServer}/test', [VpsServerController::class, 'testConnection'])->name('vps.test');
        Route::post('/vps/{vpsServer}/setup', [VpsServerController::class, 'startSetup'])->name('vps.setup');
        Route::get('/vps/{vpsServer}/setup-status', [VpsServerController::class, 'setupStatus'])->name('vps.setup-status');
        Route::post('/vps/{vpsServer}/primary', [VpsServerController::class, 'setPrimary'])->name('vps.primary');
        Route::delete('/vps/{vpsServer}', [VpsServerController::class, 'destroy'])->name('vps.destroy');
        Route::get('/dns', [PageController::class, 'dns'])->name('dns');
        Route::get('/database', [PageController::class, 'database'])->name('database');
        Route::get('/backup', [PageController::class, 'backup'])->name('backup');
        Route::get('/billing', [PageController::class, 'billing'])->name('billing');
        Route::get('/billing/checkout/{subdomain}', [PageController::class, 'openCheckout'])->name('billing.checkout');
        Route::get('/support', [PageController::class, 'support'])->name('support');
        Route::get('/pengumuman', [PageController::class, 'pengumuman'])->name('pengumuman');
        Route::post('/pengumuman', [AnnouncementController::class, 'store'])->name('pengumuman.store');
        Route::delete('/pengumuman/{announcement}', [AnnouncementController::class, 'destroy'])->name('pengumuman.destroy');
        Route::get('/settings', [PageController::class, 'settings'])->name('settings');
        Route::post('/settings/pricing', [PricingSettingsController::class, 'update'])->name('settings.pricing');
        Route::get('/log', [PageController::class, 'log'])->name('log');

        Route::get('/affiliate', [AdminAffiliateController::class, 'index'])->name('affiliate');
        Route::post('/affiliate/pengaturan', [AdminAffiliateController::class, 'updateSettings'])->name('affiliate.settings');
        Route::get('/affiliate/pencairan', [AdminAffiliateController::class, 'payouts'])->name('affiliate.payouts');
        Route::post('/affiliate/pencairan/{payout}/bayar', [AdminAffiliateController::class, 'payPayout'])->name('affiliate.payouts.pay');
        Route::post('/affiliate/pencairan/{payout}/tolak', [AdminAffiliateController::class, 'rejectPayout'])->name('affiliate.payouts.reject');
        Route::post('/affiliate/komisi/{commission}/batal', [AdminAffiliateController::class, 'cancelCommission'])->name('affiliate.commissions.cancel');
        Route::get('/affiliate/{affiliate}', [AdminAffiliateController::class, 'show'])->name('affiliate.show');
        Route::post('/affiliate/{affiliate}', [AdminAffiliateController::class, 'update'])->name('affiliate.update');
        Route::post('/affiliate/{affiliate}/status', [AdminAffiliateController::class, 'setStatus'])->name('affiliate.status');
        Route::post('/affiliate/{affiliate}/reset-password', [AdminAffiliateController::class, 'resetPassword'])->name('affiliate.reset-password');
        Route::post('/affiliate/{affiliate}/rekening/verifikasi', [AdminAffiliateController::class, 'verifyBank'])->name('affiliate.bank.verify');
        Route::post('/affiliate/{affiliate}/rekening/tolak', [AdminAffiliateController::class, 'rejectBank'])->name('affiliate.bank.reject');
        Route::get('/affiliate/{affiliate}/dokumen/{type}', [AdminAffiliateController::class, 'document'])->whereIn('type', ['ktp', 'bank_book'])->name('affiliate.document');
    });
});
