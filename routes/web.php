<?php

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

$router->group(['prefix' => 'V4'], function () use ($router) {
    $router->post('/', 'MainController@main');
    $router->post('/getConsent', 'MainController@getConsent');
    $router->post('/initiatePayment', 'MainController@initiatePayment');
    $router->post('/getPaymentStatus', 'MainController@getPaymentStatus');
});

$router->get('/', function () use ($router) {
    return $router->app->version();
});