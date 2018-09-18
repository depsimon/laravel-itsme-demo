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

Auth::routes();

Route::get('/auth/itsme', 'Auth\ItsmeController@redirect')->name('itsme.redirect');
Route::get('/auth/itsme/callback', 'Auth\ItsmeController@callback')->name('itsme.callback');

Route::get('/home', 'HomeController@index')->name('home');
