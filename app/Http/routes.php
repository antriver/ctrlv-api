<?php

use Illuminate\Routing\Router;

/**
 * @var Router $router
 */

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

$router->get('/', ['uses' => 'RootController@getIndex']);

$router->get('uploader', ['uses' => 'UploaderController@getIndex']);
$router->get('uploader/xdframe', ['uses' => 'UploaderController@getXdframe']);
$router->get('uploader/blank', ['uses' => 'UploaderController@getBlank']);

$router->resource('album', 'AlbumController', ['only' => ['store', 'show', 'update', 'destroy']]);

$router->get('image/{image}/image', ['uses' => 'ImageController@view']);
$router->get('image/{image}/thumbnail', ['uses' => 'ImageController@viewThumbnail']);
$router->post('image/{image}/rotate', ['uses' => 'ImageController@rotate']);
$router->post('image/{image}/crop', ['uses' => 'ImageController@crop']);
$router->post('image/{image}/uncrop', ['uses' => 'ImageController@uncrop']);
$router->get('image/{image}/annotation', ['uses' => 'ImageController@viewAnnotation']);
$router->post('image/{image}/annotation', ['uses' => 'ImageController@storeAnnotation']);
$router->delete('image/{image}/annotation', ['uses' => 'ImageController@destroyAnnotation']);
$router->resource('image', 'ImageController', ['only' => ['store', 'show', 'update', 'destroy']]);


$router->resource('user', 'UserController', ['only' => ['store', 'show', 'update']]);
