<?php namespace Config;
$routes = Services::routes(true);

//модуль управления способами оплат
$routes->group('pays', ['namespace' => 'Pays\Controllers'], function ($routes) {
	$routes->add('/', 'Pays::index');
	$routes->add('edit/(:num)', 'Pays::edit/$1');
	$routes->add('add', 'Pays::add');
	$routes->add('delete/(:num)', 'Pays::delete/$1');
	$routes->add('active/(:num)', 'Pays::active/$1');
});
