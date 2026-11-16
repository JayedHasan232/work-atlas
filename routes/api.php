<?php

use App\Modules\Publishing\Http\Controllers\Api\FieldIndexController;
use App\Modules\Publishing\Http\Controllers\Api\OccupationIndexController;
use App\Modules\Publishing\Http\Controllers\Api\OccupationShowController;
use App\Modules\Publishing\Http\Controllers\Api\OpenApiController;
use App\Modules\Publishing\Http\Controllers\Api\SearchController;
use Illuminate\Support\Facades\Route;

// The public read-only API (PLAN M21). Every public read goes through the Publishing presenters; don't
// add routes that bypass them. Each route is described in resources/api/openapi.v1.yaml (OpenApiSpecTest).

Route::prefix('v1')->name('api.v1.')->middleware('throttle:public-api')->group(function (): void {
    Route::get('occupations', OccupationIndexController::class)->name('occupations.index');
    Route::get('occupations/{slug}', OccupationShowController::class)
        ->where('slug', '[a-z0-9]+(-[a-z0-9]+)*')
        ->name('occupations.show');
    Route::get('fields', FieldIndexController::class)->name('fields.index');
    Route::get('search', SearchController::class)->name('search');
    Route::get('openapi.json', OpenApiController::class)->name('openapi');
});
