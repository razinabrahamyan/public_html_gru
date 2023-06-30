<?php namespace Config;
$routes = Services::routes(true);

//Модуль Wayforpay
$routes->group('wayforpay', ['namespace' => 'Wayforpay\Controllers'], function ($routes) {
	$routes->add('pay/(:num)', 'Wayforpay::pay/$1');
	$routes->add('check/(:num)', 'Wayforpay::check/$1');
	$routes->add('returnurl/(:num)', 'Wayforpay::returnurl/$1');
});