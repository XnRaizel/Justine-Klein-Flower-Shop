<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AdminController extends Controller {
	public function __construct() {
		parent::__construct();
		bloom_session(); // needed for flash messages and the CSRF token
		$this->call->model(array('Product_model', 'Order_model', 'Delivery_model', 'User_model', 'Category_model'));
	}

	private function page($view, $title, $extra = array()) {
		$this->call->view('admin/' . $view, array_merge(array('title' => $title), $extra));
	}

	public function login()      { $this->page('login', 'Staff & Admin Login'); }

	public function dashboard() {
		$this->page('dashboard', 'Dashboard', array(
			'pending'    => $this->Order_model->count_by_status('pending'),
			'delivering' => $this->Order_model->count_by_status('out_for_delivery'),
			'low_stock'  => $this->Product_model->count_low_stock(),
			'week_sales' => $this->Order_model->sales_since(date('Y-m-d H:i:s', strtotime('-7 days'))),
			'recent'     => $this->Order_model->list_recent(5),
		));
	}

	public function orders()     { $this->page('orders',     'Orders',     array('orders'     => $this->Order_model->list_all())); }
	public function deliveries() { $this->page('deliveries', 'Deliveries', array('deliveries' => $this->Delivery_model->list_all())); }
	public function inventory()  { $this->page('inventory',  'Inventory',  array('products'   => $this->Product_model->list_admin())); }
	public function staff()      { $this->page('staff',      'Staff & Roles', array('staff'    => $this->User_model->list_staff())); }

	public function reports() {
		$since = date('Y-m-d 00:00:00', strtotime('-6 days'));
		$this->page('reports', 'Sales Reports', array(
			'days' => $this->Order_model->report_by_day($since),
			'top'  => $this->Order_model->report_top_products(5),
		));
	}

	/* ================= Checking 3: Product CRUD ================= */

	/** READ (list) with optional search. */
	public function products() {
		$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
		$this->page('products', 'Products', array(
			'products' => $this->Product_model->list_admin($q),
			'q'        => $q,
		));
	}

	/** READ (one record). */
	public function product_view($id) {
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { show_404(); return; }
		$this->page('product_view', $product['name'], array(
			'product' => $product,
			'usage'   => $this->Product_model->usage($id),
		));
	}

	/** CREATE: show the empty form. */
	public function product_create() {
		$this->product_form('create', array('category_id' => '', 'name' => '', 'description' => '', 'price' => '', 'stock_qty' => '0', 'low_stock_level' => '5'), array());
	}

	/** CREATE: validate and insert. */
	public function product_store() {
		if ( ! $this->check_csrf()) { return; }
		list($clean, $errors) = $this->Product_model->validate($_POST);
		if ($errors) { $this->product_form('create', $clean, $errors); return; }
		try {
			$new_id = $this->Product_model->create_product($clean);
		} catch (Exception $e) {
			error_log('Bloom Express create product: ' . $e->getMessage());
			$this->product_form('create', $clean, array('_form' => 'The product could not be saved because of a database error. Please try again.'));
			return;
		}
		flash_set('success', 'Product "' . $clean['name'] . '" was added (ID ' . (int) $new_id . ').');
		redirect('admin/products');
		return;
	}

	/** UPDATE: show the form filled with the saved values. */
	public function product_edit($id) {
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { show_404(); return; }
		$this->product_form('edit', $product, array(), $product);
	}

	/** UPDATE: validate and save to the same row. */
	public function product_update($id) {
		if ( ! $this->check_csrf()) { return; }
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { $this->missing(); return; }
		list($clean, $errors) = $this->Product_model->validate($_POST, $id);
		if ($errors) { $this->product_form('edit', $clean, $errors, $product); return; }
		try {
			$this->Product_model->update_product($id, $clean);
		} catch (Exception $e) {
			error_log('Bloom Express update product: ' . $e->getMessage());
			$this->product_form('edit', $clean, array('_form' => 'The changes could not be saved because of a database error. Please try again.'), $product);
			return;
		}
		flash_set('success', 'Product "' . $clean['name'] . '" was updated.');
		redirect('admin/products');
		return;
	}

	/** Archive or restore (soft hide, keeps order history). */
	public function product_archive($id) {
		if ( ! $this->check_csrf()) { return; }
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { $this->missing(); return; }
		$archive = ! (int) $product['is_archived'];
		try {
			$this->Product_model->set_archived($id, $archive);
			flash_set('success', 'Product "' . $product['name'] . '" was ' . ($archive ? 'archived and hidden from customers.' : 'restored and is visible in the shop again.'));
		} catch (Exception $e) {
			error_log('Bloom Express archive product: ' . $e->getMessage());
			flash_set('error', 'The product status could not be changed because of a database error.');
		}
		redirect('admin/products');
		return;
	}

	/** DELETE step 1: confirmation page (nothing is deleted here). */
	public function product_confirm_delete($id) {
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { show_404(); return; }
		$this->page('product_delete', 'Delete product', array(
			'product'    => $product,
			'usage'      => $this->Product_model->usage($id),
			'can_delete' => $this->Product_model->can_delete($id),
		));
	}

	/** DELETE step 2: POST only, CSRF checked, blocked when history exists. */
	public function product_destroy($id) {
		if ( ! $this->check_csrf()) { return; }
		$product = $this->Product_model->find_admin($id);
		if ( ! $product) { $this->missing(); return; }
		if ( ! $this->Product_model->can_delete($id)) {
			flash_set('error', '"' . $product['name'] . '" has order or stock history and cannot be deleted. Archive it instead.');
			redirect('admin/products');
			return;
		}
		try {
			$this->Product_model->delete_product($id);
			flash_set('success', 'Product "' . $product['name'] . '" was deleted.');
		} catch (Exception $e) {
			error_log('Bloom Express delete product: ' . $e->getMessage());
			flash_set('error', 'The product could not be deleted because of a database error.');
		}
		redirect('admin/products');
		return;
	}

	/* ---- small helpers ---- */

	private function product_form($mode, array $values, array $errors, $product = null) {
		$this->page('product_form', $mode === 'create' ? 'Add product' : 'Edit product', array(
			'mode'       => $mode,
			'values'     => $values,
			'errors'     => $errors,
			'product'    => $product,
			'categories' => $this->Category_model->list_all(),
		));
	}

	/** Stops forged or expired form posts. */
	private function check_csrf() {
		if (csrf_valid()) { return true; }
		flash_set('error', 'Your form expired or was invalid. Please try again.');
		redirect('admin/products');
		return false;
	}

	private function missing() {
		flash_set('error', 'That product no longer exists.');
		redirect('admin/products');
	}
}
