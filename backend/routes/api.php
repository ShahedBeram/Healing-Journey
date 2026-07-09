<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Profile\ChildController;
use App\Http\Controllers\Api\Profile\DonorController;
use App\Http\Controllers\Api\Communication\ContactMessageController;
use App\Http\Controllers\Api\Admin\VerificationController;
use App\Http\Controllers\Api\Profile\SettingsController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Awareness\AwarenessMotivationalController;
use App\Http\Controllers\Api\Specialist\ActivitySessionController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\Awareness\AwarenessHubController;
use App\Http\Controllers\Api\Session\ActivitySessionControllerHub;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\AdminActionController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\Admin\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// مسارات عامة (لا تحتاج Token)
Route::get('/home', [HomeController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/contact', [ContactMessageController::class, 'store']);
Route::get('system/settings', [SettingsController::class, 'getSystemSettings']);

// مسارات محمية
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::post('/settings/update', [SettingsController::class, 'updateGeneralSettings']);

    // بروفايل المستخدم
    Route::prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::get('/', 'show');
        Route::post('/update', 'update');
    });
    // recovered Only
    Route::middleware('check.role:recovered_child')->prefix('profile')->controller(ProfileController::class)->group(function () {
        Route::post('/invitations/{invitationId}/handle', 'handleInvitation');
        Route::get('/all-invitations', 'getAllInvitations');
        Route::get('/all-contents', 'getAllContents');
        Route::get('/all-sessions', 'getAllSessions');
    });

    // (Parent Only)
    Route::middleware('check.role:parent')
        ->prefix('parent/children')
        ->name('parent.children.')
        ->controller(ChildController::class)
        ->group(function () {
            Route::get('/{id}', 'show')->name('show');
            Route::post('/', 'store')->name('store');
            Route::post('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::prefix('{childId}/content')->group(function () {
                Route::post('/', 'storeContent')->name('content.store');
                Route::post('/{contentId}', 'updateContent')->name('content.update');
                Route::delete('/{contentId}', 'destroyContent')->name('content.destroy');
            });
        });

    // (Donor Only)
    Route::middleware('check.role:donor')
        ->prefix('donor')
        ->controller(DonorController::class)
        ->group(function () {
            // Route::get('/campaigns', 'index');
            Route::post('/campaigns', 'store');
            Route::post('/campaigns/{id}', 'update');
            Route::delete('/campaigns/{id}', 'destroy');
        });

    // (Specialist Only) - مسارات الجلسات والأنشطة
    Route::middleware('check.role:specialist')
        ->prefix('specialist')
        ->name('specialist.')
        ->group(function () {
            Route::prefix('sessions')->controller(ActivitySessionController::class)->group(function () {
                Route::get('/recovered-children', [ActivitySessionController::class, 'getRecoveredChildren']);
                Route::post('/', 'store')->name('store');
                Route::post('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });
        });

    // (Awareness and Motivational Content)
    Route::middleware('check.role:specialist,recovered_child')
        ->prefix('awareness')
        ->controller(AwarenessMotivationalController::class)
        ->group(function () {
            Route::post('/', 'store')->name('awareness.store');
            Route::post('/{contentId}', 'update')->name('awareness.update');
            Route::delete('/{contentId}', 'destroy')->name('awareness.destroy');
        });

    // الصفحات المركزية

    // الحملات
    Route::middleware('check.role:parent,donor')->group(function () {
        Route::get('/campaigns', [CampaignController::class, 'index']);
    });
    //  محتوى الأطفال
    Route::middleware('check.role:parent,recovered_child,specialist')->group(function () {
        Route::get('/child-contents', [App\Http\Controllers\Api\ChildContentController::class, 'index']);
    });
    // المحتوى التوعوي و التحفيزي
    Route::middleware('check.role:parent,recovered_child,specialist')->group(function () {
        Route::get('/awareness', [AwarenessHubController::class, 'index']);
    });
    // الجلسات و الانشطة 
    Route::middleware('check.role:parent,recovered_child,specialist')->group(function () {
        Route::get('/session', [ActivitySessionControllerHub::class, 'index']);
    });

    // interaction
    Route::post('/interact/{type}/{id}/like', [InteractionController::class, 'toggleLike']);
    Route::post('/interact/{type}/{id}/comment', [InteractionController::class, 'storeComment']);
    Route::get('/interact/{type}/{id}/comments', [InteractionController::class, 'getComments']);

    // parent view InteractionOptions (only child_content)
    Route::get('/interact/{type}/{id}/options', [InteractionController::class, 'getInteractionOptions']);


    // (Admin Only)
    Route::middleware('check.role:admin')->prefix('admin')->group(function () {
        // لوحة التحكم (الداشبورد)
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');

        // عمليات إدارة المحتوى
        Route::prefix('actions')->controller(AdminActionController::class)->group(function () {
            Route::get('/meta/child-content/{id}', 'meta');
            Route::get('/show/{type}/{id}', 'show');       // لجلب التفاصيل للـ Popup
            Route::post('/approve/{type}/{id}', 'approve'); // للموافقة
            Route::post('/reject/{type}/{id}', 'reject');   // للرفض
        });
        // مسارات إدارة المستخدمين 
        Route::prefix('users')->controller(UserController::class)->group(function () {
            Route::get('/', 'index'); // لجلب القائمة مع البحث والـ pagination
            Route::patch('/{id}/toggle-status', 'toggleStatus'); // لتفعيل أو تعطيل الحساب
        });

        // مسارات الرسائل
        Route::prefix('messages')->name('admin.messages.')->controller(ContactMessageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{id}/reply', 'reply')->name('reply');
        });

        //مسار  تحديث إعدادات المنصة
        Route::post('system/settings/update', [SettingsController::class, 'updateSystemSettings']);

        //  مسارات التصنيفات
        Route::prefix('categories')->name('admin.categories.')->controller(CategoryController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{id}', 'update')->name('update');
            Route::patch('/{id}/toggle-status', 'toggleStatus')->name('toggle-status');
            Route::delete('/{id}', 'destroy')->name('destroy');
        });

        //  مسارات التحقق من الحسابات
        Route::controller(VerificationController::class)->group(function () {
            Route::get('/verifications', 'index');
            Route::get('/verifications/{userId}', 'show');
            Route::post('/verifications/{userId}/approve', 'approve');
            Route::post('/verifications/{userId}/reject', 'reject');
            Route::get('/documents/{userId}/view/{type}', 'viewDocument');
            Route::get('/documents/{userId}/download/{type}', 'downloadDocument');
        });
    });
});
