<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::post('/preview', 'HTMLController@preview');
Auth::routes();
Route::group(['middleware' => 'guest'], function () {
    Route::post('/preview', 'HTMLController@preview');
    Route::get('/html/templates/', 'HTMLController@templates');
    Route::get('/pdf/', 'PDFController@index');
    Route::post('/previewPdf', 'PDFController@previewPdf');
});
Route::get('/export', function () {
    $exitCode = Artisan::call('html:pdf', [
        'filename' => 1, '--1' => 'default'
    ]);

    //
});
Route::get('/home', 'HomeController@index');
