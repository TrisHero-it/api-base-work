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
