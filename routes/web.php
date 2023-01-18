<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Http\Controllers\FilesServer;

# TODO uncomment when front end will be ready
//$router->group(['prefix' => 'api'], function () use ($router) {
//    $router->post('login', ['uses' => 'AuthController@login']);
//    $router->post('register', ['uses' => 'AuthController@register']);
//
//    $router->group(['middleware' => 'auth'], function () use ($router) {
//        $router->post('logout', ['uses' => 'AuthController@logout']);
//        $router->get('pics', ['uses' => 'FilesServer@allCamFiles']);
//        $router->get('pics/{id}', ['uses' => 'FilesServer@showFiles']);
//        $router->get('/allfiles/', ['uses' => 'FilesServer@allCamFiles']);
//        $router->get('/allfiles/total', ['uses' => 'FilesServer@getTotalStats']);
//        $router->get('/allfiles/details', ['uses' => 'FilesServer@allFilesDetails']);
//        $router->get('/showfolder/{folder}', ['uses' => 'FilesServer@allFilesInFolder']);
//    });
//});

# TODO delete below  when front end will be ready
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->post('login', ['uses' => 'AuthController@login']);
    $router->post('register', ['uses' => 'AuthController@register']);

    $router->post('logout', ['uses' => 'AuthController@logout']);
    $router->get('pics', ['uses' => 'FilesServer@allCamFiles']);
    $router->get('pics/{id}', ['uses' => 'FilesServer@showFiles']);
    $router->get('/allfiles/', ['uses' => 'FilesServer@allCamFiles']);
    $router->get('/allfiles/total', ['uses' => 'FilesServer@getTotalStats']);
    $router->get('/allfiles/details', ['uses' => 'FilesServer@allFilesDetails']);
    $router->get('/showfolder/{folder}', ['uses' => 'FilesServer@allFilesInFolder']);

});

$router->get('/{any:.*}', function ($any) use ($router) {
    return json_encode(["response" => "bad request"]);
});

