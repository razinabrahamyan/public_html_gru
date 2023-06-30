<?php namespace Config;
$routes = Services::routes(true);

$routes->group('tronapi', ['namespace' => 'Tronapi\Controllers'], function ($routes) {
	$routes->add('hook', 'Tronapi::hook');
	$routes->add('test', 'Tronapi::test');
	$routes->add('cron', 'Tronapi::cron');
});
