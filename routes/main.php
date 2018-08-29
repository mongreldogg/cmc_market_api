<?php

use Core\Route;
use Core\Response;
use Bundle\MarketPrice;

Route::on(NOTFOUND, function () {
    Response::JSON([
        'error' => 'NOT_FOUND',
        ]);
});

Route::on('', function () {
    Response::JSON([
        'error' => 'NOT_FOUND',
    ]);
});

Route::on('price', function () {
    @$prices = MarketPrice::SerializeSet(MarketPrice::Select([]), 'market_id');
    if ($prices) {
        Response::JSON([
                'status' => 'OK',
                'prices' => $prices,
            ]);
    } else {
        Response::JSON([
                'status' => 'fail',
                'error' => 'Can\'t fetch market prices',
            ]);
    }
});
