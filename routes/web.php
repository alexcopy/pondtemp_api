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

$router->get('/', function () use ($router) {
    return $router->app->version();
});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('pics', ['uses' => 'FilesServer@allCamFiles']);
    $router->get('pics/{id}', ['uses' => 'FilesServer@showFiles']);
    $router->get('/allfiles/', ['uses' => 'FilesServer@allCamFiles']);
    $router->get('/allfiles/total', ['uses' => 'FilesServer@getTotalStats']);
    $router->get('/allfiles/details', ['uses' => 'FilesServer@allFilesDetails']);
    $router->get('/showfolder/{folder}', ['uses' => 'FilesServer@allFilesInFolder']);

});
