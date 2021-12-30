<?php

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

use App\Http\Controllers\EntrySyncInfoController;
use App\Http\Controllers\FcmTokenController;
use App\Http\Controllers\FoldersController;
use App\Http\Controllers\ShareableLinksController;
use App\Http\Controllers\SharesController;
use App\Http\Controllers\SpaceUsageController;
use App\Http\Controllers\StarredEntriesController;
use Common\Auth\Controllers\GetAccessTokenController;
use Common\Auth\Controllers\RegisterController;
use Common\Core\Controllers\BootstrapController;
use Common\Files\Chunks\ChunkedUploadsController;
use Common\Files\Controllers\DownloadFileController;
use Common\Files\Controllers\FileEntriesController;
use Common\Files\Controllers\RestoreDeletedEntriesController;
use Common\Localizations\LocalizationsController;
use Common\Notifications\NotificationSubscriptionsController;

Route::group(['prefix' => 'v1'], function() {
    Route::group(['middleware' => 'auth:sanctum'], function() {
        // SHARING
        Route::post('entries/add-users', [SharesController::class, 'addUsers']);
        Route::post('entries/remove-user/{userId}', [SharesController::class, 'removeUser']);
        Route::put('entries/update-user/{userId}', [SharesController::class, 'changePermissions']);

        // SHAREABLE LINK
        Route::get('entries/{id}/shareable-link', [ShareableLinksController::class, 'show']);
        Route::post('entries/{id}/shareable-link', [ShareableLinksController::class, 'store']);
        Route::put('shareable-links/{id}', [ShareableLinksController::class, 'update']);
        Route::delete('shareable-links/{id}', [ShareableLinksController::class, 'destroy']);

        // ENTRIES
        Route::post('entries/sync-info', [EntrySyncInfoController::class, 'index']);
        Route::get('entries', 'DriveEntriesController@index');
        Route::post('entries', '\Common\Files\Controllers\FileEntriesController@store');
        Route::post('entries/move', 'MoveFileEntriesController@move');
        Route::post('entries/copy', 'CopyEntriesController@copy');
        Route::post('entries/restore', [RestoreDeletedEntriesController::class, 'restore']);
        Route::put('entries/{id}', [FileEntriesController::class, 'update']);
        Route::delete('entries', [FileEntriesController::class, 'destroy']);

        // UPLOADS
        Route::post('uploads', [FileEntriesController::class, 'store']);
        Route::post('uploads/sessions/load', [ChunkedUploadsController::class, 'load']);
        Route::post('uploads/sessions/chunks', [ChunkedUploadsController::class, 'storeChunk']);

        // FOLDERS
        Route::post('folders', [FoldersController::class, 'store']);

        // STARRING
        Route::post('entries/star', [StarredEntriesController::class, 'add']);
        Route::post('entries/unstar', [StarredEntriesController::class, 'remove']);

        //SPACE USAGE
        Route::get('user/space-usage', [SpaceUsageController::class, 'index']);

        // FCM TOKENS
        Route::post('fcm-token', [FcmTokenController::class, 'store']);

        // NOTIFICATIONS
        Route::get('notifications/{userId}/subscriptions', [NotificationSubscriptionsController::class, 'index']);
        Route::put('notifications/{userId}/subscriptions', [NotificationSubscriptionsController::class, 'update']);
    });

    // FILE PREVIEW / DOWNLOAD
    Route::get('uploads/{id}', [FileEntriesController::class, 'show']);
    Route::get('entries/download', [DownloadFileController::class, 'download']);

    // AUTH
    Route::post('auth/register', [RegisterController::class, 'register']);
    Route::post('auth/login', [GetAccessTokenController::class, 'login']);
    Route::get('auth/social/{provider}/callback', '\Common\Auth\Controllers\SocialAuthController@loginCallback');
    Route::post('auth/password/email', '\Common\Auth\Controllers\SendPasswordResetEmailController@sendResetLinkEmail');

    // REMOTE CONFIG
    Route::get('remote-config/mobile', [BootstrapController::class, 'getMobileBootstrapData']);

    // LOCALIZATIONS
    Route::get('localizations/{name}', [LocalizationsController::class, 'show']);
});

