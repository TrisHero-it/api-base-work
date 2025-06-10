<?php

use App\Models\Account;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use GuzzleHttp\Promise;
use Illuminate\Http\Request;

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
    return redirect('/api/telescope');
});

Route::get('/getcode', function (Request $request) {
    return view('get-code.index');
});

Route::get('/test', function () {
    phpinfo();
});

Route::middleware('auth.basic')->group(function () {
    Route::get('a', function () {
        return redirect('https://docs.google.com/spreadsheets/d/1nSiTn8ETSRtlTrSfY_8cIekGlZa50x7B9d2H4qZr0gI/edit?usp=sharing');
    });
});
