<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
    /***********************************************************************************/
    /********************************    USER     **************************************/
    /***********************************************************************************/
    // Route::post('checkUsernamePwdBeforeLogin', 'API\UserController@checkUsernamePwdBeforeLogin');
    // Route::get('user', 'API\UserController@getUserByToken');
    // Route::get('user/registration/{token}', 'API\UserController@getUserForRegistration');
    // Route::post('register/{token}', 'API\UserController@registerWithToken');

    Route::post('login', 'API\UserController@login');
    Route::post('logout', 'API\UserController@logout');
    Route::post('register', 'API\UserController@register');

    // handle reset password form process
    Route::prefix('password')->group(function () {
        Route::post('forgot', 'Auth\ForgotPasswordController@getResetToken');
        Route::get('reset/{token}/{email}', 'Auth\ResetPasswordController@reset')->name('password.reset')->middleware('signed');
        Route::post('reset', 'Auth\ResetPasswordController@callResetPassword');
        Route::post('update', 'API\UserController@updatePasswordBeforeLogin');
    });

    Route::prefix('email')->group(function () {
        Route::get('verify/{id}/{hash}', 'API\UserController@verify')->name('api.verification.verify')->middleware('signed');
        Route::post('resend', 'API\UserController@resendVerification');
        Route::post('registrationLink', 'API\UserController@sendRegistrationLink');
    });
});

/***********************************************************************************/
/*****************************    AUTHENTICATED ************************************/
/***********************************************************************************/

Route::group(['middleware' => 'auth:api'], function () {
    /***********************************************************************************/
    /********************************    USERS    **************************************/
    /***********************************************************************************/
    Route::prefix('user-management')->group(function () {
        Route::get('index', 'API\UserController@index');
        Route::get('show/{id}', 'API\UserController@show');
        Route::post('store', 'API\UserController@store');
        Route::put('update-account', 'API\UserController@updateAccount');
        Route::put('update-password', 'API\UserController@updatePassword');
        Route::put('update/{id}/work-hours', 'API\UserController@updateWorkHours');
        Route::put('update/{id}', 'API\UserController@update');
        Route::put('restore/{id?}', 'API\UserController@restore');
        Route::put('destroy/{id?}', 'API\UserController@destroy');
        Route::put('force-destroy/{id?}', 'API\UserController@forceDestroy');
    });


    /***********************************************************************************/
    /********************************    ROLES    **************************************/
    /***********************************************************************************/
    Route::prefix('role-management')->group(function () {
        Route::get('index', 'API\RoleController@index');
        Route::get('show/{id}', 'API\RoleController@show');
        Route::post('store', 'API\RoleController@store');
        Route::put('update/{id}', 'API\RoleController@update');
        Route::put('restore/{id?}', 'API\RoleController@restore');
        Route::put('destroy/{id?}', 'API\RoleController@destroy');
        Route::put('force-destroy/{id?}', 'API\RoleController@forceDestroy');
    });

    /***********************************************************************************/
    /********************************    PERMISSIONS  **********************************/
    /***********************************************************************************/
    Route::prefix('permission-management')->group(function () {
        Route::get('index', 'API\PermissionController@index');
        Route::get('show/{id}', 'API\PermissionController@show');
        Route::post('store', 'API\PermissionController@store');
        Route::post('update/{id}', 'API\PermissionController@update');
        Route::delete('destroy/{id}', 'API\PermissionController@destroy');
    });

    /***********************************************************************************/
    /********************************   COMPANIES **************************************/
    /***********************************************************************************/
    Route::prefix('company-management')->group(function () {
        Route::get('index', 'API\CompanyController@index');
        Route::get('show/{id}', 'API\CompanyController@show');
        Route::post('store', 'API\CompanyController@store');
        Route::put('update/{id}', 'API\CompanyController@update');
        Route::put('restore/{id?}', 'API\CompanyController@restore');
        Route::put('destroy/{id?}', 'API\CompanyController@destroy');
        Route::put('force-destroy/{id?}', 'API\CompanyController@forceDestroy');
        Route::put('duplicate/{id?}', 'API\CompanyController@duplicate');
    });

    /***********************************************************************************/
    /*********************************   SKILLS   **************************************/
    /***********************************************************************************/
    Route::prefix('skill-management')->group(function () {
        Route::get('index', 'API\SkillController@index');
        Route::get('index/task/{id}', 'API\SkillController@getByTaskId');
        Route::get('show/{id}', 'API\SkillController@show');
        Route::post('store', 'API\SkillController@store');
        Route::put('update/{id}', 'API\SkillController@update');
        Route::put('restore/{id?}', 'API\SkillController@restore');
        Route::put('destroy/{id?}', 'API\SkillController@destroy');
        Route::put('force-destroy/{id?}', 'API\SkillController@forceDestroy');
    });

    /***********************************************************************************/
    /*******************************   WORKAREAS   *************************************/
    /***********************************************************************************/
    Route::prefix('workarea-management')->group(function () {
        Route::get('index', 'API\WorkareaController@index');
        Route::get('show/{id}', 'API\WorkareaController@show');
        Route::post('store', 'API\WorkareaController@store');
        Route::put('update/{id}', 'API\WorkareaController@update');
        Route::put('restore/{id?}', 'API\WorkareaController@restore');
        Route::put('destroy/{id?}', 'API\WorkareaController@destroy');
        Route::put('force-destroy/{id?}', 'API\WorkareaController@forceDestroy');
    });

    /***********************************************************************************/
    /********************************   PROJETCS   *************************************/
    /***********************************************************************************/
    Route::prefix('project-management')->group(function () {
        Route::get('index', 'API\ProjectController@index');
        Route::get('show/{id}', 'API\ProjectController@show');
        Route::post('store', 'API\ProjectController@store');
        Route::post('start/{id}', 'API\ProjectController@start');
        Route::post('reStart', 'API\ProjectController@reStart');
        Route::get('workHoursPeriods', 'API\ProjectController@workHoursPeriods');
        Route::get('unavailablePeriods', 'API\ProjectController@unavailablePeriods');
        Route::post('store-range/{id}', 'API\ProjectController@addRange');
        Route::put('update/{id}', 'API\ProjectController@update');
        Route::get('updateTaskPeriod', 'API\ProjectController@updateTaskPeriod');
        Route::get('setProjectStandby', 'API\ProjectController@setProjectStandby');
        Route::put('restore/{id?}', 'API\ProjectController@restore');
        Route::put('destroy/{id?}', 'API\ProjectController@destroy');
        Route::put('force-destroy/{id?}', 'API\ProjectController@forceDestroy');
    });

    /***********************************************************************************/
    /********************************    RANGES    **************************************/
    /***********************************************************************************/
    Route::prefix('range-management')->group(function () {
        Route::get('index', 'API\RangeController@index');
        Route::get('show/{id}', 'API\RangeController@show');
        Route::post('store', 'API\RangeController@store');
        Route::put('update/{id}', 'API\RangeController@update');
        Route::put('restore/{id?}', 'API\RangeController@restore');
        Route::put('destroy/{id?}', 'API\RangeController@destroy');
        Route::put('force-destroy/{id?}', 'API\RangeController@forceDestroy');
    });

    /***********************************   TASK   **************************************/
    /***********************************************************************************/
    Route::prefix('task-management')->group(function () {
        Route::get('index', 'API\TaskController@index');
        Route::get('comments', 'API\TaskController@comments');
        Route::get('taskTimeSpent', 'API\TaskController@taskTimeSpent');
        Route::get('task/{id}', 'API\TaskController@getTask');
        Route::get('show/{id}', 'API\TaskController@show');
        Route::post('store', 'API\TaskController@store');
        Route::post('store-comment/{id}', 'API\TaskController@addComment');
        Route::put('update/{id}', 'API\TaskController@update');
        Route::put('update-partial/{id}', 'API\TaskController@updatePartial');
        Route::put('destroy/{id?}', 'API\TaskController@destroy');
        Route::put('force-destroy/{id?}', 'API\TaskController@forceDestroy');

    });

    /*****************************   REPETITIVE - TASK   ********************************/
    /***********************************************************************************/
    Route::prefix('repetitive-task-management')->group(function () {
        Route::get('range/{id}', 'API\RangeController@getRepetitiveTasks');
    });

    /***********************************************************************************/
    /*****************************   UNAVAILABILITIES   ********************************/
    /***********************************************************************************/
    Route::prefix('unavailability-management')->group(function () {
        Route::get('index', 'API\UnavailabilityController@index');
        Route::get('show/{id}', 'API\UnavailabilityController@show');
        Route::post('store', 'API\UnavailabilityController@store');
        Route::put('update/{id}', 'API\UnavailabilityController@update');
        Route::put('destroy/{id?}', 'API\UnavailabilityController@destroy');
    });

    /***********************************************************************************/
    /***********************************   Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('hours-management')->group(function () {
        Route::get('index', 'API\HoursController@index');
        Route::get('show/{id}', 'API\HoursController@show');
        Route::post('store', 'API\HoursController@store');
        Route::put('update/{id}', 'API\HoursController@update');
        Route::put('destroy/{id?}', 'API\HoursController@destroy');
    });

    /***********************************************************************************/
    /***********************************   Dealing Hours   *************************************/
    /***********************************************************************************/
    Route::prefix('dealing-hours-management')->group(function () {
        Route::group(['middleware' => ['can:read,App\Models\DealingHours,user_id']], function () {
            Route::get('index', 'API\DealingHoursController@index');
            Route::get('overtimes/{user_id}', 'API\DealingHoursController@getOvertimes');
        });
        Route::group(['middleware' => ['can:show,dealing_hours']], function () {
            Route::get('show/{dealing_hours}', 'API\DealingHoursController@show');
        });
        Route::group(['middleware' => ['can:publish,App\Models\DealingHours']], function () {
            Route::post('store', 'API\DealingHoursController@store');
            Route::post('storeOrUpdateUsed', 'API\DealingHoursController@storeOrUpdateUsed');
        });
        Route::group(['middleware' => ['can:edit,dealing_hours']], function () {
            Route::post('update/{dealing_hours}', 'API\DealingHoursController@update');
        });
        Route::group(['middleware' => ['can:delete,dealing_hours']], function () {
            Route::delete('destroy/{dealing_hours}', 'API\DealingHoursController@destroy');
            Route::delete('forceDelete/{dealing_hours}', 'API\DealingHoursController@forceDelete');
        });
    });

    /***********************************************************************************/
    /***********************************   Customers   *************************************/
    /***********************************************************************************/
    Route::prefix('customer-management')->group(function () {
        Route::get('index', 'API\CustomerController@index');
        Route::get('show/{id}', 'API\CustomerController@show');
        Route::post('store', 'API\CustomerController@store');
        Route::put('update/{id}', 'API\CustomerController@update');
        Route::put('restore/{id?}', 'API\CustomerController@restore');
        Route::put('destroy/{id?}', 'API\CustomerController@destroy');
        Route::put('force-destroy/{id?}', 'API\CustomerController@forceDestroy');
    });

    /***********************************************************************************/
    /************************************   Bugs   *************************************/
    /***********************************************************************************/
    Route::prefix('bug-management')->group(function () {
        Route::get('index', 'API\BugController@index');
        Route::get('show/{id}', 'API\BugController@show');
        Route::post('store', 'API\BugController@store');
        Route::put('update/{id}', 'API\BugController@update');
        Route::put('restore/{id?}', 'API\BugController@restore');
        Route::put('destroy/{id?}', 'API\BugController@destroy');
        Route::put('force-destroy/{id?}', 'API\BugController@forceDestroy');
    });

    /***********************************************************************************/
    /***********************************   Modules   *************************************/
    /***********************************************************************************/
    Route::prefix('module-management')->group(function () {
        Route::get('index', 'API\ModuleController@index');
        Route::get('data-types', 'API\ModuleController@dataTypes');
        Route::post('test', 'API\ModuleController@testConnection');
        Route::get('show/{id}', 'API\ModuleController@show');
        Route::post('store', 'API\ModuleController@store');
        Route::get('sync/{id}', 'API\ModuleController@sync');
        Route::put('update/{id}', 'API\ModuleController@update');
        Route::put('update-connection/{id}', 'API\ModuleController@updateConnection');
        Route::put('update-data-types/{id}', 'API\ModuleController@updateDataTypes');
        Route::put('destroy/{id?}', 'API\ModuleController@destroy');
    });

    /***********************************************************************************/
    /********************************** Documents **************************************/
    /***********************************************************************************/
    Route::prefix('document-management')->group(function () {
        Route::post('store', 'API\DocumentController@store');
        Route::post('upload/{token}', 'API\DocumentController@upload');
        Route::put('destroy/{id?}', 'API\DocumentController@destroy');
    });

    /***********************************************************************************/
    /********************************* Subscription ************************************/
    /***********************************************************************************/
    Route::prefix('subscription-management')->group(function () {
        Route::get('index', 'API\SubscriptionController@index');
        Route::get('packages', 'API\SubscriptionController@packages');
        Route::get('show/{id}', 'API\SubscriptionController@show');
        Route::post('store', 'API\SubscriptionController@store');
        Route::put('update/{id}', 'API\SubscriptionController@update');
        Route::put('restore/{id?}', 'API\SubscriptionController@restore');
        Route::put('destroy/{id?}', 'API\SubscriptionController@destroy');
        Route::put('force-destroy/{id?}', 'API\SubscriptionController@forceDestroy');
    });

    /***********************************************************************************/
    /*********************************** Packages **************************************/
    /***********************************************************************************/
    Route::prefix('package-management')->group(function () {
        Route::get('index', 'API\PackageController@index');
        Route::get('show/{id}', 'API\PackageController@show');
    });
    
    /************************************************************************************/
    /***********************************  Todo List  ************************************/
    /***********************************************************************************/
    Route::prefix('todo-management')->group(function () {
        Route::get('index', 'API\TodoController@index');
        Route::get('todo/{id}', 'API\TodoController@getTodo');
        Route::get('show/{id}', 'API\TodoController@show');
        Route::post('store', 'API\TodoController@store');
        Route::put('update/{id}', 'API\TodoController@update');
        Route::put('update-partial/{id}', 'API\TodoController@updatePartial');
        Route::put('destroy/{id?}', 'API\TodoController@destroy');
        Route::put('force-destroy/{id?}', 'API\TodoController@forceDestroy');

    });
     /************************************************************************************/
    /***********************************  Tag  ************************************/
    /***********************************************************************************/
    Route::prefix('tag-management')->group(function () {
        Route::get('index', 'API\TagController@index');
        Route::get('tag/{id}', 'API\TagController@getTag');
        Route::get('show/{id}', 'API\TagController@show');
        Route::post('store', 'API\TagController@store');
        Route::put('update/{id}', 'API\TagController@update');
        Route::put('update-partial/{id}', 'API\TagController@updatePartial');
        Route::put('destroy/{id?}', 'API\TagController@destroy');
        Route::put('force-destroy/{id?}', 'API\TagController@forceDestroy');

    });

      /***********************************************************************************/
    /*********************************   Supply   **************************************/
    /***********************************************************************************/
    Route::prefix('supply-management')->group(function () {
        Route::get('index', 'API\SupplyController@index');
        Route::get('index/task/{id}', 'API\SupplyController@getByTaskId');
        Route::get('show/{id}', 'API\SupplyController@show');
        Route::post('store', 'API\SupplyController@store');
        Route::put('update/{id}', 'API\SupplyController@update');
        Route::post('updateTaskSupplyReceived', 'API\SupplyController@updateTaskSupplyReceived');
        Route::put('restore/{id?}', 'API\SupplyController@restore');
        Route::put('destroy/{id?}', 'API\SupplyController@destroy');
        Route::put('force-destroy/{id?}', 'API\SupplyController@forceDestroy');
    });
});
/***********************************************************************************/
/***************************** NOT AUTHENTICATED ***********************************/
/***********************************************************************************/

Route::get('document-management/show/{path}', 'API\DocumentController@show');


/***********************************************************************************/
/************************************* APK *****************************************/
/***********************************************************************************/
Route::get('download-app', function () {
    return response()->download(public_path('storage/Plannigo_V0.apk'), 'Plannigo.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
        'Content-Disposition' => 'attachment; filename="Plannigo.apk"',
    ]);
});
