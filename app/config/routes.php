<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * Justine Klein Flower Shop - URI routing
 * The React app handles customer and workspace pages; LavaLust provides
 * the app shell and same-origin API endpoints.
 */
/** @var object $router **/

// ---------- Public / customer site ----------
$router->get('/',                           'ReactController::app');
$router->get('/about',                      'ReactController::app');
$router->get('/db-check',                   'DbController::check');

$router->get('/shop',                       'ReactController::app');
$router->get('/shop/occasion/{slug}',       'ReactController::app');
$router->get('/product/{id}',               'ReactController::app')->where_number('id');

$router->get('/cart',                       'ReactController::app');
$router->get('/checkout',                   'ReactController::app');
$router->get('/track',                      'ReactController::app');
$router->get('/account/orders',             'ReactController::app');
$router->get('/custom-orders',              'ReactController::app');
$router->get('/custom-orders/{id}',         'ReactController::app')->where_number('id');

$router->get('/login',                      'ReactController::app');
$router->get('/register',                   'ReactController::app');
$router->get('/staff',                      'ReactController::app');
$router->get('/staff/{section}',            'ReactController::app');
$router->get('/staff/{section}/{id}',       'ReactController::app')->where_number('id');
$router->get('/rider',                      'ReactController::app');
$router->get('/rider/deliveries',           'ReactController::app');

// Same-origin React API
$router->get('/api/v1/auth/session',             'ApiController::session_info');
$router->get('/api/v1/payment-options',          'ApiController::payment_options');
$router->post('/webhooks/paymongo',              'ApiController::paymongo_webhook');
$router->post('/api/v1/auth/login',              'ApiController::login');
$router->post('/api/v1/auth/register',           'ApiController::register');
$router->post('/api/v1/auth/logout',             'ApiController::logout');
$router->get('/api/v1/catalog',                  'ApiController::catalog');
$router->get('/api/v1/products/{id}',            'ApiController::product')->where_number('id');
$router->get('/api/v1/cart',                     'ApiController::cart');
$router->post('/api/v1/cart/items',              'ApiController::cart_add');
$router->patch('/api/v1/cart/items/{id}',        'ApiController::cart_update')->where_number('id');
$router->delete('/api/v1/cart/items/{id}',       'ApiController::cart_remove')->where_number('id');
$router->get('/api/v1/orders',                   'ApiController::orders');
$router->post('/api/v1/orders',                  'ApiController::create_order');
$router->post('/api/v1/orders/{id}/cancel',      'ApiController::cancel_order')->where_number('id');
$router->patch('/api/v1/orders/{id}/visibility', 'ApiController::set_order_visibility')->where_number('id');
$router->post('/api/v1/orders/{id}/deposit',     'ApiController::retry_deposit')->where_number('id');
$router->post('/api/v1/orders/track',            'ApiController::track_order');
$router->get('/api/v1/custom-orders',            'ApiController::custom_orders');
$router->post('/api/v1/custom-orders',           'ApiController::custom_order_create');
$router->get('/api/v1/custom-orders/{id}',       'ApiController::custom_order');
$router->post('/api/v1/custom-orders/{id}/messages', 'ApiController::custom_order_message')->where_number('id');
$router->post('/api/v1/custom-orders/{id}/decision', 'ApiController::custom_order_decision')->where_number('id');
$router->get('/api/v1/admin/dashboard',          'ApiController::admin_dashboard');
$router->get('/api/v1/admin/products',           'ApiController::admin_products');
$router->post('/api/v1/admin/products',          'ApiController::admin_product_create');
$router->get('/api/v1/admin/products/categories','ApiController::admin_categories');
$router->get('/api/v1/admin/products/{id}',      'ApiController::admin_product')->where_number('id');
$router->put('/api/v1/admin/products/{id}',      'ApiController::admin_product_update')->where_number('id');
$router->patch('/api/v1/admin/products/{id}/archive', 'ApiController::admin_product_archive')->where_number('id');
$router->delete('/api/v1/admin/products/{id}',   'ApiController::admin_product_delete')->where_number('id');
$router->get('/api/v1/admin/orders',             'ApiController::admin_orders');
$router->patch('/api/v1/admin/orders/{id}/archive', 'ApiController::admin_archive_order')->where_number('id');
$router->patch('/api/v1/admin/orders/{id}/deposit', 'ApiController::admin_confirm_cash_deposit')->where_number('id');
$router->patch('/api/v1/admin/orders/{id}/status', 'ApiController::admin_advance_order_status')->where_number('id');
$router->patch('/api/v1/admin/orders/{id}/balance', 'ApiController::admin_confirm_cash_balance')->where_number('id');
$router->post('/api/v1/admin/orders/{id}/refund', 'ApiController::admin_record_deposit_refund')->where_number('id');
$router->get('/api/v1/admin/deliveries',         'ApiController::admin_deliveries');
$router->patch('/api/v1/admin/deliveries/{id}/archive', 'ApiController::admin_archive_delivery')->where_number('id');
$router->patch('/api/v1/admin/deliveries/{id}/rider', 'ApiController::admin_assign_delivery')->where_number('id');
$router->get('/api/v1/admin/inventory',          'ApiController::admin_inventory');
$router->get('/api/v1/admin/staff',              'ApiController::admin_staff');
$router->patch('/api/v1/admin/staff/{id}/role',  'ApiController::admin_staff_role')->where_number('id');
$router->get('/api/v1/admin/riders',             'ApiController::admin_riders');
$router->get('/api/v1/admin/reports',            'ApiController::admin_reports');
$router->get('/api/v1/admin/custom-orders',      'ApiController::admin_custom_orders');
$router->get('/api/v1/admin/custom-orders/{id}', 'ApiController::admin_custom_order')->where_number('id');
$router->post('/api/v1/admin/custom-orders/{id}/messages', 'ApiController::admin_custom_order_message')->where_number('id');
$router->post('/api/v1/admin/custom-orders/{id}/convert', 'ApiController::admin_custom_order_convert')->where_number('id');
$router->get('/api/v1/rider/deliveries',         'ApiController::rider_deliveries');
$router->post('/api/v1/rider/deliveries/{id}/confirm', 'ApiController::rider_confirm_delivery')->where_number('id');

// ---------- Admin / staff dashboard ----------
$router->group(array('prefix' => '/admin'), function ($router) {
	$router->get('/',            'ReactController::app');
	$router->get('/login',       'ReactController::app');
	$router->get('/products',    'ReactController::app');
	$router->get('/products/create', 'ReactController::app');
	$router->post('/products/store', 'AdminController::product_store');
	$router->get('/products/{id}', 'ReactController::app')->where_number('id');
	$router->get('/products/{id}/edit', 'ReactController::app')->where_number('id');
	$router->post('/products/{id}/update', 'AdminController::product_update')->where_number('id');
	$router->post('/products/{id}/archive', 'AdminController::product_archive')->where_number('id');
	$router->get('/products/{id}/delete', 'ReactController::app')->where_number('id');
	$router->post('/products/{id}/delete', 'AdminController::product_destroy')->where_number('id');
	$router->get('/orders',      'ReactController::app');
	$router->get('/custom-orders', 'ReactController::app');
	$router->get('/custom-orders/{id}', 'ReactController::app')->where_number('id');
	$router->get('/deliveries',  'ReactController::app');
	$router->get('/inventory',   'ReactController::app');
	$router->get('/staff',       'ReactController::app');
	$router->get('/reports',     'ReactController::app');
});
