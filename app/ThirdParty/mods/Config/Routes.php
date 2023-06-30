<?php namespace Config;
$routes = Services::routes(true);

//модуль управления категориями модификаторов
$routes->group('groups', ['namespace' => 'Mods\Controllers'], function ($routes) {
	$routes->add('/', 'Groups::index');
	$routes->add('edit/(:num)', 'Groups::edit/$1');
	$routes->add('add', 'Groups::add');
	$routes->add('delete/(:num)', 'Groups::delete/$1');
});

//модуль управления модификатороами
$routes->group('mods', ['namespace' => 'Mods\Controllers'], function ($routes) {
	$routes->add('items/(:num)', 'Mods::items/$1');
	$routes->add('edit/(:num)', 'Mods::edit/$1');
	$routes->add('add/(:num)', 'Mods::add/$1');
	$routes->add('delete/(:num)', 'Mods::delete/$1');
});
