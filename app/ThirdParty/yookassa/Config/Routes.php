<?php namespace Config;
$routes = Services::routes(true);

//модуль Yookassa
$routes->group('yookassa', ['namespace' => 'Yookassa\Controllers'], function ($routes) {
	$routes->add('/', 'Yookassa::index');
	$routes->add('pay/(:num)', 'Yookassa::pay/$1');
	$routes->add('test', 'Yookassa::test');
	$routes->add('check/(:num)', 'Yookassa::check/$1');
});