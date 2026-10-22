<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * Bloom Express - URI routing
 * Checking 1: initial routes and basic page navigation.
 * Checking 3: product CRUD routes (create, read, update, delete) under /admin/products.
 */
/** @var object $router **/

// ---------- Public / customer site ----------
$router->get('/',                           'HomeController::index');
$router->get('/about',                      'HomeController::about');
$router->get('/db-check',                   'DbController::check');

$router->get('/shop',                       'ShopController::index');
$router->get('/shop/occasion/{slug}',       'ShopController::occasion');
$router->get('/product/{id}',               'ShopController::show')->where_number('id');

$router->get('/cart',                       'CartController::index');
$router->get('/checkout',                   'CartController::checkout');
$router->get('/track',                      'OrderController::track');
$router->get('/account/orders',             'OrderController::history');

$router->get('/login',                      'AuthController::login');
$router->get('/register',                   'AuthController::register');

// ---------- Admin / staff dashboard ----------
$router->group(array('prefix' => '/admin'), function ($router) {
	$router->get('/',            'AdminController::dashboard');
	$router->get('/login',       'AdminController::login');
	$router->get('/products',    'AdminController::products');

	// Product CRUD (fixed words such as "create" come before {id})
	$router->get('/products/create',              'AdminController::product_create');
	$router->post('/products/store',              'AdminController::product_store');
	$router->get('/products/{id}',                'AdminController::product_view')->where_number('id');
	$router->get('/products/{id}/edit',           'AdminController::product_edit')->where_number('id');
	$router->post('/products/{id}/update',        'AdminController::product_update')->where_number('id');
	$router->post('/products/{id}/archive',       'AdminController::product_archive')->where_number('id');
	$router->get('/products/{id}/delete',         'AdminController::product_confirm_delete')->where_number('id');
	$router->post('/products/{id}/delete',        'AdminController::product_destroy')->where_number('id');

	$router->get('/orders',      'AdminController::orders');
	$router->get('/deliveries',  'AdminController::deliveries');
	$router->get('/inventory',   'AdminController::inventory');
	$router->get('/staff',       'AdminController::staff');
	$router->get('/reports',     'AdminController::reports');
});
