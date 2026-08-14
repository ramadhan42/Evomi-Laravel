<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\DisclaimerController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\KurirController;
use App\Http\Controllers\Api\NewsletterController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderPaymentController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\Api\PaymentGatewayController;
use App\Http\Controllers\Api\PaymentSettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PromoController;
use App\Http\Controllers\Api\QuizAdminController;
use App\Http\Controllers\Api\QuizController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\ContactMessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:60,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
Route::post('/logout-beacon', [AuthController::class, 'logoutBeacon']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

Route::get('/quiz/questions', [QuizController::class, 'getQuestions']);
Route::get('/quiz/results', [QuizController::class, 'getResults']);

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);

// Contact: kirim pesan public; list butuh auth (lihat controller)
Route::get('/contact', [ContactMessageController::class, 'index']);
Route::get('/contact-show', [ContactMessageController::class, 'show']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::get('/contact/unread-count', [ContactMessageController::class, 'getUnreadCount']);
Route::post('/contact/mark-read', [ContactMessageController::class, 'markUserRead']);

// Tracking: public read (lacak paket), write di auth
Route::get('/trackings/{resi}', [OrderTrackingController::class, 'show']);

Route::post('/checkout/guest', [OrderController::class, 'guestCheckout']);
Route::post('/guest/cart-email', [GuestController::class, 'emailCart'])->middleware('throttle:8,1');
Route::post('/guest/orders', [GuestController::class, 'orders'])->middleware('throttle:12,1');
Route::post('/guest/trackings', [GuestController::class, 'trackings'])->middleware('throttle:12,1');

Route::get('/disclaimers', [DisclaimerController::class, 'index']);
Route::get('/disclaimers/{id}', [DisclaimerController::class, 'show']);
Route::get('/kurirs', [KurirController::class, 'index']);
Route::get('/kurirs/{id}', [KurirController::class, 'show']);
Route::get('/promos', [PromoController::class, 'index']);
Route::get('/promos/{id}', [PromoController::class, 'show']);

Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);

Route::get('/cms/faqs', [CmsController::class, 'publicFaqs']);
Route::get('/cms/{page}', [CmsController::class, 'showPage']);

Route::get('/payment-settings', [PaymentSettingController::class, 'publicShow']);

Route::post('/payments/xendit/qr', [PaymentGatewayController::class, 'createXenditQr']);
Route::get('/payments/xendit/qr/{id}', [PaymentGatewayController::class, 'showXenditQr']);
Route::post('/payments/xendit/va', [PaymentGatewayController::class, 'createXenditVa']);
Route::get('/payments/xendit/va/{id}', [PaymentGatewayController::class, 'showXenditVa']);
Route::post('/payments/midtrans/qris', [PaymentGatewayController::class, 'createMidtransQris']);
Route::get('/payments/midtrans/qris/{orderId}', [PaymentGatewayController::class, 'showMidtransQris']);
Route::post('/payments/midtrans/va', [PaymentGatewayController::class, 'createMidtransVa']);
Route::get('/payments/midtrans/va/{orderId}', [PaymentGatewayController::class, 'showMidtransVa']);
Route::post('/payments/midtrans/snap', [PaymentGatewayController::class, 'createMidtransSnap']);
Route::post('/payments/midtrans/notification', [PaymentGatewayController::class, 'midtransNotification']);
Route::post('/payments/xendit/notification', [PaymentGatewayController::class, 'xenditNotification']);

Route::get('/payments/orders/{invoiceId}', [OrderPaymentController::class, 'show']);
Route::post('/payments/orders/{invoiceId}/sync', [OrderPaymentController::class, 'sync']);
Route::post('/orders/{invoiceId}/payment-intent', [OrderPaymentController::class, 'attachIntent']);

/*
|--------------------------------------------------------------------------
| Protected routes (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'last.seen'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user/profile', [UserController::class, 'show']);
    Route::post('/user/profile', [UserController::class, 'update']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::delete('/user/profile', [UserController::class, 'destroy']);

    // Alias lama yang masih dipakai beberapa helper frontend
    Route::get('/profile', [UserController::class, 'show']);

    Route::apiResource('carts', CartController::class)->only(['index', 'store', 'destroy', 'update']);
    Route::apiResource('wishlists', WishlistController::class)->only(['index', 'store', 'destroy']);
    Route::get('/wishlists/{wishlist}', [WishlistController::class, 'show']);
    // Alias singular (frontend getWishlistDetail)
    Route::get('/wishlist/{wishlist}', [WishlistController::class, 'show']);

    Route::post('/quiz/submit', [QuizController::class, 'submitQuiz']);
    Route::get('/quiz/history', [QuizController::class, 'history']);

    Route::get('/shopping-history', [UserController::class, 'shoppingHistory']);
    Route::get('/badges', [UserController::class, 'badges']);
    Route::get('/payments/pending', [OrderPaymentController::class, 'pending']);
    Route::post('/payments/orders/{invoiceId}/cancel', [OrderPaymentController::class, 'cancel']);
    Route::get('/my-trackings', [OrderTrackingController::class, 'mine']);

    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::patch('/orders/{id}/confirm', [OrderController::class, 'confirmReceipt']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    // Alias history detail
    Route::get('/history/{id}', [OrderController::class, 'show']);

    // Checkout membuat tracking setelah bayar
    Route::post('/trackings', [OrderTrackingController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Admin routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/orders', [OrderController::class, 'getAllOrders']);
        Route::get('/carts', [CartController::class, 'getAllCarts']);
        Route::get('/wishlists', [WishlistController::class, 'getAllWishlists']);
        Route::get('/users', [UserController::class, 'getAllUsers']);
        Route::get('/revenue', [OrderController::class, 'getTotalRevenue']);
        Route::post('/users/{id}', [UserController::class, 'updateByAdmin']);
        Route::put('/users/{id}', [UserController::class, 'updateByAdmin']);
        Route::delete('/users/{id}', [UserController::class, 'destroyByAdmin']);
        Route::get('/subscribers', [NewsletterController::class, 'index']);

        Route::post('/disclaimers', [DisclaimerController::class, 'store']);
        Route::put('/disclaimers/{id}', [DisclaimerController::class, 'update']);
        Route::delete('/disclaimers/{id}', [DisclaimerController::class, 'destroy']);

        Route::get('/kurirs', [KurirController::class, 'index']);
        Route::post('/kurirs', [KurirController::class, 'store']);
        Route::put('/kurirs/{id}', [KurirController::class, 'update']);
        Route::delete('/kurirs/{id}', [KurirController::class, 'destroy']);

        Route::get('/promos', [PromoController::class, 'index']);
        Route::post('/promos', [PromoController::class, 'store']);
        Route::put('/promos/{id}', [PromoController::class, 'update']);
        Route::delete('/promos/{id}', [PromoController::class, 'destroy']);

        Route::get('/articles', [ArticleController::class, 'adminIndex']);
        Route::get('/articles/{id}', [ArticleController::class, 'adminShow']);
        Route::post('/articles', [ArticleController::class, 'store']);
        Route::post('/articles/{id}', [ArticleController::class, 'update']);
        Route::put('/articles/{id}', [ArticleController::class, 'update']);
        Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);

        Route::post('/contact/{id}/reply', [ContactMessageController::class, 'reply']);
        Route::get('/contact/conversations', [ContactMessageController::class, 'conversations']);
        Route::get('/contact/thread', [ContactMessageController::class, 'thread']);
        Route::post('/contact/thread/send', [ContactMessageController::class, 'sendToUser']);
        Route::delete('/contact/thread', [ContactMessageController::class, 'destroyConversation']);

        Route::get('/trackings', [OrderTrackingController::class, 'index']);
        Route::put('/trackings/{order_id}', [OrderTrackingController::class, 'update']);
        Route::delete('/trackings/{order_id}', [OrderTrackingController::class, 'destroy']);

        // Quiz management (soal, jawaban, skor)
        Route::get('/quiz/questions', [QuizAdminController::class, 'indexQuestions']);
        Route::post('/quiz/questions', [QuizAdminController::class, 'storeQuestion']);
        Route::get('/quiz/questions/{id}', [QuizAdminController::class, 'showQuestion']);
        Route::put('/quiz/questions/{id}', [QuizAdminController::class, 'updateQuestion']);
        Route::delete('/quiz/questions/{id}', [QuizAdminController::class, 'destroyQuestion']);

        Route::post('/quiz/options', [QuizAdminController::class, 'storeOption']);
        Route::put('/quiz/options/{id}', [QuizAdminController::class, 'updateOption']);
        Route::delete('/quiz/options/{id}', [QuizAdminController::class, 'destroyOption']);

        Route::get('/quiz/scores', [QuizAdminController::class, 'indexScores']);
        Route::get('/quiz/scores/{id}', [QuizAdminController::class, 'showScore']);
        Route::put('/quiz/scores/{id}', [QuizAdminController::class, 'updateScore']);
        Route::delete('/quiz/scores/{id}', [QuizAdminController::class, 'destroyScore']);

        Route::get('/quiz/results', [QuizAdminController::class, 'indexResults']);
        Route::put('/quiz/results/{personalityKey}', [QuizAdminController::class, 'updateResult']);

        // CMS
        Route::get('/cms/faqs', [CmsController::class, 'adminFaqs']);
        Route::post('/cms/faqs', [CmsController::class, 'storeFaq']);
        Route::put('/cms/faqs/{id}', [CmsController::class, 'updateFaq']);
        Route::delete('/cms/faqs/{id}', [CmsController::class, 'destroyFaq']);
        Route::post('/cms/upload', [CmsController::class, 'upload']);
        Route::get('/cms/{page}', [CmsController::class, 'adminShowPage']);
        Route::put('/cms/{page}', [CmsController::class, 'adminUpdatePage']);

        Route::get('/payment-settings', [PaymentSettingController::class, 'show']);
        Route::put('/payment-settings', [PaymentSettingController::class, 'update']);
        Route::patch('/payment-settings', [PaymentSettingController::class, 'update']);

        Route::get('/products', [ProductController::class, 'adminIndex']);
    });

    // Product write + order status + tracking update (admin)
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::post('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);

        Route::get('/trackings', [OrderTrackingController::class, 'index']);
        Route::put('/trackings/{order_id}', [OrderTrackingController::class, 'update']);
        Route::delete('/trackings/{order_id}', [OrderTrackingController::class, 'destroy']);
    });
});
