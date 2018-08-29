<?php

use Core\Route;
use Core\Request;
use Core\Response;
use Core\Event;

Route::on('update_prices', function () {
    $password = Request::Request()['password'];
    if ($password != SCHEDULER_PASSWORD) {
        exit;
    }
    Response::JSON(['status' => 'OK', 'response' => Event::raise('api::updatePrices')]);
});
