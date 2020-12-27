<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShowsController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function() {
    Route::get('/home', [HomeController::class, 'home'])->name('home.unsubscribed');
});

Route::middleware(['auth', 'subscribed'])->group(function() {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/unseen', [HomeController::class, 'unseen'])->name('home.unseen');

    Route::get('/tv-shows', [ShowsController::class, 'index'])->name('shows');
    Route::get('/tv-shows/{show}', [ShowsController::class, 'view'])->name('shows.view');
    Route::post('/tv-shows/{show}', [ShowsController::class, 'watched'])->name('shows.watched');
    Route::post('/tv-shows/{show}/season/{season}', [ShowsController::class, 'seasonWatched'])->name('seasons.watched');

    Route::get('/watch/{episode}', [ShowsController::class, 'watch'])->name('episodes.watch');
    Route::post('/watch/{episode}', [ShowsController::class, 'episodeWatched'])->name('episodes.watched');

    Route::post('/watch-status/{episode}', [WatchController::class, 'time'])->name('watch.time');
    Route::put('/watch-status/{episode}', [WatchController::class, 'ended'])->name('watch.ended');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'save'])->name('settings.save');
});

require __DIR__.'/auth.php';
