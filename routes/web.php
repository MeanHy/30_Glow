<?php

use App\Http\Controllers\Pusher\PusherController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::post('/pusher/user-auth', [PusherController::class, 'pusherAuth']);

Route::get('/', function () {
    return redirect()->route('admin.home');
});

Route::get('/not-found', function () {
    return Inertia::render('Errors/NotFound');
})->name('notfound');

Route::get('/forbidden', function () {
    return Inertia::render('Errors/403/Forbidden');
})->name('forbidden');

Route::fallback(function () {
    return redirect()->route('notfound');
});

Route::middleware(['auth', 'auth.session', 'web', 'authen:Super Admin,Manager'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\Revenue\RevenueController::class, 'index'])->name('home');
    Route::get('/dailyProductRevenues', [App\Http\Controllers\Revenue\RevenueController::class, 'billProDuctsList'])->name('dailyProductRevenues');
    Route::get('/dailyProductRevenues/{id}/edit', [App\Http\Controllers\Revenue\RevenueController::class, 'billProDuctsDetail'])->name('dailyProductRevenues-detail');
    Route::get('/dailyServiceRevenues', [App\Http\Controllers\Revenue\RevenueController::class, 'billServicesList'])->name('dailyServiceRevenues');
    Route::get('/dailyServiceRevenues/{id}/edit', [App\Http\Controllers\Revenue\RevenueController::class, 'billServicesDetail'])->name('dailyServiceRevenues-detail');
    // Module User
    Route::resource('users', App\Http\Controllers\Users\UserController::class)->names('user');
    Route::controller(App\Http\Controllers\Users\UserController::class)->group(function () {
        Route::post('/users/{id}/restore', 'restore')->name('users.restore');
        Route::delete('/users/{id}/permanent', 'permanent')->name('users.permanent');
    });
    // Module Role
    Route::resource('roles', App\Http\Controllers\Roles\RoleController::class)->names('role');
    Route::post('handleRole/permission/{id}', [App\Http\Controllers\Roles\RoleController::class, 'givePermissionToRole']);
    // Module Permission
    Route::resource('permissions', App\Http\Controllers\Permissions\PermissionController::class)->names('permission');
    // Module Customer
    Route::resource('customers', App\Http\Controllers\Customers\CustomerController::class)->names('customers');
    Route::post('/customers/{id}/reset-password', [App\Http\Controllers\Customers\CustomerController::class, 'resetPassword']);
    // Module Category
    Route::resource('categories', App\Http\Controllers\Categories\CategoriesController::class)->names('categories');
    Route::controller(App\Http\Controllers\Categories\CategoriesController::class)->group(function () {
        Route::post('/categories/{id}/restore', 'restore')->name('products.restore');
        Route::delete('/categories/{id}/permanent', 'permanent')->name('products.permanent');
    });
    //Module Brand
    Route::resource('brands', App\Http\Controllers\Brands\BrandsController::class)->names('brands');
    Route::controller(App\Http\Controllers\Brands\BrandsController::class)->group(function () {
        Route::post('/brands/{id}/restore', 'restore')->name('products.restore');
        Route::delete('/brands/{id}/permanent', 'permanent')->name('products.permanent');
    });
    // Module Product
    Route::resource('products', App\Http\Controllers\Products\ProductController::class)->names('products');
    Route::controller(App\Http\Controllers\Products\ProductController::class)->group(function () {
        Route::post('/products/{id}/restore', 'restore')->name('products.restore');
        Route::delete('/products/{id}/permanent', 'permanent')->name('products.permanent');
    });
    // Module Gallery
    Route::resource('galleries', App\Http\Controllers\Gallery\GalleryController::class)->names('gallery');
    // Module Biils
    Route::resource('bills', App\Http\Controllers\Bills\BillController::class)->names('bill');
    // Module Biils-services
    Route::resource('bills-services', App\Http\Controllers\BillServices\BillServicesController::class)->names('bill-services');
    // Module Service Collection
    Route::resource('services/collections', App\Http\Controllers\ServicesCollections\ServiceCollectionsContoller::class)->names('service_collections');
    // Module Service
    Route::resource('services', App\Http\Controllers\Services\ServiceController::class)->names('service');
    Route::controller(App\Http\Controllers\Services\ServiceController::class)->group(function () {
        Route::post('/services/{id}/upload', 'updateFiles')->name('services.updateImage');
        // Route::post('/services/{id}/restore', 'restore')->name('services.restore');
        // Route::delete('/services/{id}/permanent', 'permanent')->name('services.permanent');
    });
    // Module Booking
    Route::prefix('bookings')->as('bookings.')->controller(App\Http\Controllers\Bookings\BookingController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });
    // Module Sitemap
    Route::resource('sitemap', App\Http\Controllers\Sitemap\SitemapController::class)->names('sitemap');
    // Module Slides
    Route::resource('slides', App\Http\Controllers\Slides\SlidesController::class)->names('slides');
    // Module Booking
    Route::resource('bookings', App\Http\Controllers\Bookings\BookingController::class)->names('bookings');
    // Module contacts
    Route::resource('contacts', App\Http\Controllers\Contacts\ContactsController::class)->names('contacts');
    //Modele comments
    Route::resource('comments', App\Http\Controllers\Comments\CommentsController::class)->names('comments');
    // Module PostsCollections
    Route::resource('posts/collections', App\Http\Controllers\PostsCollections\PostCollectionsController::class)->names('posts-collections');
});

Route::middleware('web')->prefix('auth')->as('auth.')->controller(App\Http\Controllers\Auth\Admin\AuthenController::class)->group(function () {
    Route::get('login', 'login')->name('login');
    Route::post('login', 'handleLogin')->middleware('throttle:5,1'); // Giới hạn request 5 lần mỗi 1 phút
    Route::get('logout', 'handleLogout')->middleware('auth');
});

Route::group(['prefix' => 'laravel-filemanager', 'middleware' => ['web', 'api']], function () {
    \UniSharp\LaravelFilemanager\Lfm::routes();
});