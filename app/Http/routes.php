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

Route::model('image', '\CtrlV\Models\ImageModel');

Route::get('/', ['uses' => 'RootController@getIndex']);

Route::get('uploader', ['uses' => 'UploaderController@getIndex']);
Route::get('uploader/xdframe', ['uses' => 'UploaderController@getXdframe']);
Route::get('uploader/blank', ['uses' => 'UploaderController@getBlank']);

Route::get('image/{image}/image', ['uses' => 'ImageController@view']);
Route::get('image/{image}/thumbnail', ['uses' => 'ImageController@viewThumbnail']);
Route::post('image/{image}/rotate', ['uses' => 'ImageController@rotate']);
Route::post('image/{image}/crop', ['uses' => 'ImageController@crop']);
Route::post('image/{image}/uncrop', ['uses' => 'ImageController@uncrop']);
Route::get('image/{image}/annotation', ['uses' => 'ImageController@viewAnnotation']);
Route::post('image/{image}/annotation', ['uses' => 'ImageController@storeAnnotation']);
Route::delete('image/{image}/annotation', ['uses' => 'ImageController@destroyAnnotation']);
Route::resource('image', 'ImageController', ['only' => ['store', 'show', 'update', 'destroy']]);

Route::resource('album', 'AlbumController', ['only' => ['store', 'show', 'update', 'destroy']]);
