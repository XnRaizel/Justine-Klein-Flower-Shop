<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ShopController extends Controller {
	public function __construct() {
		parent::__construct();
		$this->call->model(array('Category_model', 'Product_model', 'Addon_model'));
	}

	public function index() {
		$this->call->view('pages/shop', array(
			'title'      => 'Shop',
			'heading'    => 'All Flowers & Bouquets',
			'categories' => $this->Category_model->list_all(),
			'products'   => $this->Product_model->list_active(),
			'current'    => '',
		));
	}

	public function occasion($slug) {
		$category = $this->Category_model->find_by_slug($slug);
		if ( ! $category) { show_404(); return; }
		$this->call->view('pages/shop', array(
			'title'      => $category['name'],
			'heading'    => $category['name'] . ' Flowers',
			'categories' => $this->Category_model->list_all(),
			'products'   => $this->Product_model->list_active($slug),
			'current'    => $slug,
		));
	}

	public function show($id) {
		$product = $this->Product_model->get_product($id);
		if ( ! $product) { show_404(); return; }
		$this->call->view('pages/product', array(
			'title'   => $product['name'],
			'product' => $product,
			'addons'  => $this->Addon_model->list_grouped(),
		));
	}
}
