<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class CartController extends Controller {
	public function __construct() {
		parent::__construct();
		$this->call->model('Cart_model');
	}

	private function cart_data($title) {
		$items = $this->Cart_model->list_items(demo_customer_id());
		$subtotal = 0;
		foreach ($items as $it) { $subtotal += $it['line_total']; }
		return array('title' => $title, 'items' => $items, 'subtotal' => $subtotal);
	}

	public function index() {
		$this->call->view('pages/cart', $this->cart_data('Shopping Cart'));
	}

	public function checkout() {
		$this->call->view('pages/checkout', $this->cart_data('Checkout'));
	}
}
