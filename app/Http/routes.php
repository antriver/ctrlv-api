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

//$router->resource('album', 'AlbumController', ['only' => ['store', 'show', 'update', 'destroy']]);


$router->post('images/{image}/annotation', ['uses' => 'AnnotationController@store']);
$router->get('images/{image}/annotation', ['uses' => 'AnnotationController@show']);
$router->delete('images/{image}/annotation', ['uses' => 'AnnotationController@destroy']);

$router->post('images', ['uses' => 'ImagesController@store']);
$router->get('images/{image}', ['uses' => 'ImagesController@show']);
$router->put('images/{image}', ['uses' => 'ImagesController@update']);
$router->delete('images/{image}', ['uses' => 'ImagesController@destroy']);

$router->get('images/{image}/image', ['uses' => 'ImagesController@view']);
$router->put('images/{image}/image', ['uses' => 'ImagesController@updateImage']);
$router->get('images/{image}/thumbnail', ['uses' => 'ImagesController@viewThumbnail']);
$router->post('images/{image}/crop', ['uses' => 'ImagesController@crop']);
$router->delete('images/{image}/crop', ['uses' => 'ImagesController@uncrop']);

$router->post('sessions', ['uses' => 'SessionsController@login']);
$router->get('sessions/{session}', ['uses' => 'SessionsController@show']);

$router->post('users', ['uses' => 'UsersController@store']);
$router->get('users/{user}', ['uses' => 'UsersController@show']);
$router->put('users/{user}', ['uses' => 'UsersController@update']);
// $router->delete('users/{user}', ['uses' => 'UsersController@destroy']);

$router->get('users/{user}/images', ['uses' => 'UsersController@showImages']);
