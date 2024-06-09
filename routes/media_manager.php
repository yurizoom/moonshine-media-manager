<?php

use Illuminate\Support\Facades\Route;
use YuriZoom\MoonShineMediaManager\Controllers\MediaManagerController;

Route::group([
    'prefix' => config('moonshine.route.prefix'),
    'as' => 'moonshine.',
    'middleware' => [config('moonshine.auth.middleware'), 'web'],
], function () {
    Route::get('media', [MediaManagerController::class, 'index'])->name('media.manager.index');
    Route::get('media/download', [MediaManagerController::class, 'download'])->name('media.manager.download');
    Route::post('media/delete', [MediaManagerController::class, 'delete'])->name('media.manager.delete');
    Route::post('media/move', [MediaManagerController::class, 'move'])->name('media.manager.move');
    Route::post('media/upload', [MediaManagerController::class, 'upload'])->name('media.manager.upload');
    Route::post('media/folder', [MediaManagerController::class, 'newFolder'])->name('media.manager.new.folder');
});
