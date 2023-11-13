<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Models\Validation;
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

Route::get('/', function () {
    $validationInstance = Validation::first();
    $valtotales = $validationInstance->total;
    // dd($valtotales);
    return view('welcome')->with('valtotales', $valtotales);
})->name('index');
Route::post('/process-csv', [Controller::class, 'processCsv']);
Route::post('/', [Controller::class, 'utilstorage']);
