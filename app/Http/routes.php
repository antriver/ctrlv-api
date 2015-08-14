<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::model('image', '\CtrlV\Models\ImageRow');

Route::get('/', ['uses' => 'RootController@getIndex']);

Route::controller('uploader', 'UploaderController');

Route::resource('image', 'ImageController', ['only' => ['store', 'show', 'update', 'destroy']]);
