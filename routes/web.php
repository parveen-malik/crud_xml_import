<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::get('/', fn() => view('welcome'));

Route::resource('Contact', ContactController::class);

Route::get('Contact-import', [ContactController::class,'importForm'])->name('Contact.import.form');
Route::post('Contact-import', [ContactController::class,'import'])->name('Contact.import');
Route::get('Contact-import-progress', [ContactController::class,'progress'])->name('Contact.import.progress');
