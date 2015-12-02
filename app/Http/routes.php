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
$router->group(
    [
        'prefix' => 'v1.1'
    ],
    function (Router $router) {
        $router->get('/', ['uses' => 'RootController@getIndex']);

        $router->get('uploader', ['uses' => 'UploaderController@getIndex']);
        $router->get('uploader/xdframe', ['uses' => 'UploaderController@getXdframe']);
        $router->get('uploader/blank', ['uses' => 'UploaderController@getBlank']);

        $router->post('albums', ['uses' => 'AlbumsController@store']);
        $router->get('albums/{album}', ['uses' => 'AlbumsController@show']);
        $router->put('albums/{album}', ['uses' => 'AlbumsController@update']);
        $router->delete('albums/{album}', ['uses' => 'AlbumsController@destroy']);

        $router->get('albums/{album}/images', ['uses' => 'AlbumImagesController@index']);

        $router->post('images', ['uses' => 'ImagesController@store']);
        $router->get('images/{image}', ['uses' => 'ImagesController@show']);
        $router->put('images/{image}', ['uses' => 'ImagesController@update']);
        $router->delete('images/{image}', ['uses' => 'ImagesController@destroy']);

        $router->get('images/{image}/image', ['uses' => 'ImagesController@showImage']);
        $router->put('images/{image}/image', ['uses' => 'ImagesController@updateImage']);
        $router->get('images/{image}/thumbnail', ['uses' => 'ImagesController@showThumbnail']);

        $router->post('images/{image}/crop', ['uses' => 'ImagesController@storeCrop']);
        $router->delete('images/{image}/crop', ['uses' => 'ImagesController@destroyCrop']);

        $router->post('images/{image}/annotation', ['uses' => 'AnnotationController@store']);
        $router->get('images/{image}/annotation', ['uses' => 'AnnotationController@show']);
        $router->delete('images/{image}/annotation', ['uses' => 'AnnotationController@destroy']);

        $router->post('sessions', ['uses' => 'SessionsController@store']);
        $router->get('sessions/{session}', ['uses' => 'SessionsController@show']);
        $router->delete('sessions/{session}', ['uses' => 'SessionsController@destroy']);

        $router->post('users', ['uses' => 'UsersController@store']);
        $router->get('users/{user}', ['uses' => 'UsersController@show']);
        $router->put('users/{user}', ['uses' => 'UsersController@update']);
        // $router->delete('users/{user}', ['uses' => 'UsersController@destroy']);

        $router->get('users/{user}/albums', ['uses' => 'UsersController@indexAlbums']);
        $router->get('users/{user}/images', ['uses' => 'UsersController@indexImages']);
    }
);
