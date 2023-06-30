<?php namespace Config;
$routes = Services::routes(true);

//модуль управления продуктами
$routes->group('products', ['namespace' => 'Products\Controllers'], function ($routes) {
	$routes->add('/(:any)', 'Products::index/$1');
	$routes->post('products_', 'Products::products_');

	$routes->add('/', 'Products::index');
	$routes->add('edit/(:num)', 'Products::edit/$1');
	$routes->add('edit/(:num)/(:alpha)', 'Products::edit/$1/$2');
	$routes->add('add', 'Products::add');
	$routes->add('delete/(:num)', 'Products::delete/$1');
	$routes->add('save_/(:num)', 'Products::save_/$1');
	$routes->add('save_/(:num)/(:alpha)', 'Products::save_/$1/$2');
	$routes->add('thankyou/(:num)', 'Products::thankyou/$1');
	$routes->add('thankyou/(:num)/(:alpha)', 'Products::thankyou/$1/$2');
	$routes->add('thankyou_/(:num)', 'Products::thankyou_/$1');
	$routes->add('thankyou_/(:num)/(:alpha)', 'Products::thankyou_/$1/$2');
	$routes->add('end_month/(:num)', 'Products::end_month/$1');
	$routes->add('cron', 'Products::cron');
	$routes->add('generated', 'Products::generated');
	$routes->add('cloudpayments_auto/(:num)', 'Products::cloudpayments_auto/$1');
	$routes->add('active/(:num)', 'Products::active/$1');

	$routes->add('items/(:num)', 'Products::items/$1');
	$routes->add('add_item/(:num)', 'Products::add_item/$1');
	$routes->add('delete_item/(:num)', 'Products::delete_item/$1');
	$routes->add('edit_item/(:num)', 'Products::edit_item/$1');
	$routes->add('copy_item/(:num)', 'Products::copy_item/$1');
	$routes->add('file_id/(:num)', 'Products::file_id/$1');

	$routes->add('photos/(:num)', 'Products::photos/$1');
	$routes->add('add_photo/(:num)', 'Products::add_photo/$1');
	$routes->add('delete_photo/(:num)', 'Products::delete_photo/$1');
	$routes->add('edit_photo/(:num)', 'Products::edit_photo/$1');

	$routes->add('parsestart', 'Products::parsestart');

	$routes->add('items_kg/(:num)', 'Products::items_kg/$1');
	$routes->add('add_item_kg/(:num)', 'Products::add_item_kg/$1');
	$routes->add('delete_item_kg/(:num)', 'Products::delete_item_kg/$1');
	$routes->add('edit_item_kg/(:num)', 'Products::edit_item_kg/$1');
});

//модуль управления категориями
$routes->group('category', ['namespace' => 'Products\Controllers'], function ($routes) {
	$routes->add('/', 'Category::index');
	$routes->add('edit/(:num)', 'Category::edit/$1');
	$routes->add('add', 'Category::add');
	$routes->add('delete/(:num)', 'Category::delete/$1');
	$routes->add('tree', 'Category::tree');
});