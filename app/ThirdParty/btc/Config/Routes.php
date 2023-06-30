<?php namespace Config;
$routes = Services::routes(true);

//модуль для работы с биткоин
$routes->group('btc', ['namespace' => 'Btc\Controllers'], function ($routes) {
	$routes->add('cron', 'Btc::cron');
	$routes->add('binance', 'Btc::binance');
});
