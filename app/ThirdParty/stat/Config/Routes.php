<?php namespace Config;
$routes = Services::routes(true);

//модуль управления статистикой
$routes->group('stat', ['namespace' => 'Stat\Controllers'], function ($routes) {
	$routes->add('/', 'Stat::index');
	$routes->post('index_', 'Stat::index_');
	$routes->add('days', 'Stat::days');
	$routes->add('hours', 'Stat::hours');
});
