<?php namespace Config;
$routes = Services::routes(true);

//модуль рассылки
$routes->group('sender', ['namespace' => 'Sender\Controllers'], function ($routes) {
	$routes->add('/', 'Sender::index');
	$routes->post('index_', 'Sender::index_');
	$routes->add('stat/(:num)', 'Sender::stat/$1');
	$routes->post('stat_/(:num)', 'Sender::stat_/$1');
	$routes->add('edit/(:num)', 'Sender::edit/$1');
	$routes->add('edit/(:num)/(:alpha)', 'Sender::edit/$1/$2');
	$routes->add('add', 'Sender::add');
	$routes->add('delete/(:num)', 'Sender::delete/$1');
	$routes->add('active/(:num)', 'Sender::active/$1');
	$routes->add('save_/(:num)', 'Sender::save_/$1');
	$routes->add('save_/(:num)/(:alpha)', 'Sender::save_/$1/$2');
	$routes->add('disable_web_page_preview/(:num)', 'Sender::disable_web_page_preview/$1');
	$routes->add('need_btn/(:num)', 'Sender::need_btn/$1');
	$routes->add('file_id/(:num)', 'Sender::file_id/$1');
	$routes->add('finish/(:num)', 'Sender::finish/$1');
	$routes->add('cron', 'Sender::cron');
	$routes->add('remember', 'Sender::remember');
	$routes->add('products_/(:num)', 'Sender::products_/$1');
	$routes->add('channels_/(:num)', 'Sender::channels_/$1');
	$routes->add('sum_bonus/(:num)', 'Sender::sum_bonus/$1');
});
