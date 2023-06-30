<?php namespace Config;
$routes = Services::routes(true);

//модуль управления балансом
$routes->group('balance', ['namespace' => 'Balance\Controllers'], function ($routes) {
	$routes->add('items/(:num)', 'Balance::index/$1');
	$routes->add('edit/(:num)', 'Balance::edit/$1');
	$routes->add('add/(:num)', 'Balance::add/$1');
	$routes->add('delete/(:num)', 'Balance::delete/$1');
});