<?php namespace Config;
$routes = Services::routes(true);

//модуль управления заказами
$routes->group('orders', ['namespace' => 'Orders\Controllers'], function ($routes) {
	$routes->add('/', 'Orders::index');
	$routes->add('/(:any)', 'Orders::index/$1');
	$routes->post('orders_', 'Orders::orders_');
	$routes->post('checkbox_', 'Orders::checkbox_');
	$routes->add('edit/(:num)', 'Orders::edit/$1');
	$routes->add('add', 'Orders::add');
	$routes->add('delete/(:num)', 'Orders::delete/$1');
	$routes->add('status/(:num)', 'Orders::status/$1');
	$routes->add('delete_product/(:num)', 'Orders::delete_product/$1');
	$routes->add('edit_date/(:num)', 'Orders::edit_date/$1');
	$routes->add('stat/(:any)', 'Orders::stat/$1');
	$routes->add('prolongate', 'Orders::prolongate');
	$routes->add('cron', 'Orders::cron');
	$routes->add('reset', 'Orders::reset');
	$routes->add('export', 'Orders::export');
	$routes->add('edit_item/(:num)', 'Orders::edit_item/$1');
});

