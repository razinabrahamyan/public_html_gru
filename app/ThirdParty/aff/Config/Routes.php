<?php namespace Config;
$routes = Services::routes(true);

//модуль управления партнерской программой
$routes->group('aff', ['namespace' => 'Aff\Controllers'], function ($routes) {
	$routes->add('/', 'Aff::index');
	$routes->add('edit/(:num)', 'Aff::edit/$1');
	$routes->add('add', 'Aff::add');
	$routes->add('delete/(:num)', 'Aff::delete/$1');
	$routes->add('invited/(:num)', 'Aff::invited/$1');
});


//модуль управления бонусами
$routes->group('bonus', ['namespace' => 'Aff\Controllers'], function ($routes) {
	$routes->add('/', 'Bonus::index');
	$routes->add('edit/(:num)', 'Bonus::edit/$1');
	$routes->add('add', 'Bonus::add');
	$routes->add('delete/(:num)', 'Bonus::delete/$1');
});

