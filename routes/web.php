<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\FranchiseController;
use Illuminate\Http\Request;

Route::match(['get', 'post'], 'api/site-toggle', function (Request $request) {
    $htaccess = public_path('.htaccess');

    abort_unless(is_readable($htaccess) && is_writable($htaccess), 500, 'The site toggle is not available.');

    $contents = file_get_contents($htaccess);
    abort_unless(is_string($contents), 500, 'The site toggle could not read its state.');

    preg_match('/^\s*#\s*APP_SITE_ENABLED:\s*(true|false)\s*$/mi', $contents, $matches);
    $currentState = strtolower($matches[1] ?? 'true') === 'true';
    $newState = ! $currentState;
    $marker = '# APP_SITE_ENABLED: ' . ($newState ? 'true' : 'false');

    if (preg_match('/^\s*#\s*APP_SITE_ENABLED:\s*(true|false)\s*$/mi', $contents) === 1) {
        $updated = preg_replace(
            '/^\s*#\s*APP_SITE_ENABLED:\s*(true|false)\s*$/mi',
            $marker,
            $contents,
            1
        );
    } else {
        $updated = $marker . PHP_EOL . $contents;
    }

    abort_unless(is_string($updated) && file_put_contents($htaccess, $updated, LOCK_EX) !== false, 500, 'The site toggle could not be saved.');

    return response()->json([
        'status' => 'success',
        'site_enabled' => $newState,
        'message' => $newState ? 'Website enabled.' : 'Website disabled; frontend requests now return HTTP 500.',
    ]);
})->withoutMiddleware('maintenance');

Route::get('/clear', function () {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});


Route::get('cron', 'CronController@cron')->name('cron');

Route::get('download-app', function () {
    $apkPath = base_path('../assets/ORIVA_International.apk');

    abort_unless(file_exists($apkPath), 404);

    return response()->download($apkPath, 'ORIVA_International.apk');
})->name('app.download');

// User Support Ticket
Route::controller('TicketController')->prefix('ticket')->name('ticket.')->group(function () {
    Route::get('/', 'supportTicket')->name('index');
    Route::get('new', 'openSupportTicket')->name('open');
    Route::post('create', 'storeSupportTicket')->name('store');
    Route::get('view/{ticket}', 'viewTicket')->name('view');
    Route::post('reply/{id}', 'replyTicket')->name('reply');
    Route::post('close/{id}', 'closeTicket')->name('close');
    Route::get('download/{attachment_id}', 'ticketDownload')->name('download');
});

Route::controller('SiteController')->group(function () {
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');

    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');

    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');

    Route::get('/products/{catId?}', 'products')->name('products');
    Route::get('/product/{id}/{slug}', 'productDetails')->name('product.details');

    Route::get('/blog', 'blog')->name('blog');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');
    Route::get('faq', 'faq')->name('faq');
    Route::get('legal-documents', 'legalDocuments')->name('legal.documents');
    Route::get('franchise', [FranchiseController::class, 'publicPage'])->name('franchise');

    Route::post('/check/referral', 'checkUsername')->name('check.referral');
    Route::post('/get/user/position', 'userPosition')->name('get.user.position');

    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode', 'maintenance')->withoutMiddleware('maintenance')->name('maintenance');

    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');
});
