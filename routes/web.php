<?php

use App\Http\Controllers\ProfileController;
use App\Livewire\Donations;
use App\Livewire\Donors;
use App\Livewire\Sponsorships;
use App\Livewire\Subscriptions;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/donors', Donors\Index::class)->name('donors.index');
    Route::get('/donors/create', Donors\Create::class)->name('donors.create');
    Route::get('/donors/{donor}', Donors\Show::class)->whereNumber('donor')->name('donors.show');
    Route::get('/donations', Donations\Index::class)->name('donations.index');
    Route::get('/donors/{donor}/donations/create', Donations\Create::class)->whereNumber('donor')->name('donations.create');
    Route::get('/sponsorships', Sponsorships\Index::class)->name('sponsorships.index');
    Route::get('/donors/{donor}/sponsorships/create', Sponsorships\Create::class)->whereNumber('donor')->name('sponsorships.create');
    Route::get('/subscriptions', Subscriptions\Index::class)->name('subscriptions.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
