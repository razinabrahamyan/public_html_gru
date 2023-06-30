<?php namespace Config;
$routes = Services::routes(true);

//модуль управления языками
$routes->group('language', ['namespace' => 'Language\Controllers'], function ($routes) {
	$routes->add('/', 'Language::index');
	$routes->add('edit/(:num)', 'Language::edit/$1');
	$routes->add('add', 'Language::add');
	$routes->add('delete/(:num)', 'Language::delete/$1');
	$routes->add('deactivate/(:num)/(:num)', 'Language::deactivate/$1/$2');
	$routes->add('export/(:any)', 'Language::export/$1');
});
