<?php

use Core\Event;
use Core\Console;
use Bundle\Forehood;
use Bundle\MarketPrice;

Console::AddCommand('update_prices', function () {
    echo "Market price update scheduler process started. Press Ctrl+C to exit\r\n";
    $out = shell_exec('watch wget -S -O - http://'.DOMAIN.ROOT_DIR.
        'update_prices?password='.SCHEDULER_PASSWORD);
});

Event::add('api::updatePrices', function () {
    Event::raise('api::coinExchangeUpdate');
    Event::raise('api::cryptopiaUpdate');
    Event::raise('api::cryptoWolfUpdate');
});

Event::add('api::coinExchangeUpdate', function () {
    //CoinExchange
    Forehood::on('https://www.coinexchange.io/api/v1/getmarkets',
        function ($response) {
            //echo "called https://www.coinexchange.io/api/v1/getmarkets\r\n";
            $marketList = json_decode($response['body'], true);
            $CEMarketId = 0;
            foreach ($marketList['result'] as $market) {
                if (strtolower($market['MarketAssetName']) == COIN_MARKET_NAME
                    && strtolower($market['BaseCurrency']) == BASE_CURRENCY_NAME) {
                    $CEMarketId = $market['MarketID'];
                    break;
                }
            }
            Forehood::on("https://www.coinexchange.io/api/v1/getmarketsummary?market_id=$CEMarketId",
                function ($resp) {
                    $price = json_decode($resp['body'], true)['result']['LastPrice'];
                    echo "coinexchange price: $price\r\n";
                    $market = MarketPrice::Select([
                        'market_name' => 'coinexchange',
                    ], 1);
                    if ($market instanceof MarketPrice) {
                        $market->setMarketPrice($price);
                        $market->Save();
                    }
                });
            Forehood::request('GET', "https://www.coinexchange.io/api/v1/getmarketsummary?market_id=$CEMarketId");
        });
    Forehood::request('GET', 'https://www.coinexchange.io/api/v1/getmarkets');
});

Event::add('api::cryptopiaUpdate', function () {
    //Cryptopia
    Forehood::on('https://www.cryptopia.co.nz/api/GetMarket/'.COIN_MARKET_CODE.'_'.BASE_CURRENCY_CODE,
        function ($response) {
            $price = json_decode($response['body'], true)['Data']['LastPrice'];
            echo "cryptopia price: $price\r\n";
            $market = MarketPrice::Select([
                'market_name' => 'cryptopia',
            ], 1);
            if ($market instanceof MarketPrice) {
                $market->setMarketPrice($price);
                $market->Save();
            }
        });
    Forehood::request('GET', 'https://www.cryptopia.co.nz/api/GetMarket/'.COIN_MARKET_CODE.'_'.BASE_CURRENCY_CODE);
});

Event::add('api::cryptoWolfUpdate', function () {
    //CryptoWolf
    Forehood::on('https://external.cryptowolf.eu/stagin/php/get-rates-new.php?from='.
        COIN_MARKET_CODE.'&to='.BASE_CURRENCY_CODE,
        function ($response) {
            $price = json_decode($response['body'], true)[1][0][0];
            echo "cryptowolf price: $price\r\n";
            $market = MarketPrice::Select([
                'market_name' => 'cryptowolf',
            ], 1);
            if ($market instanceof MarketPrice) {
                $market->setMarketPrice($price);
                $market->Save();
            }
        });
    Forehood::request('GET', 'https://external.cryptowolf.eu/stagin/php/get-rates-new.php?from='.
        COIN_MARKET_CODE.'&to='.BASE_CURRENCY_CODE);
});
