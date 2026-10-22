<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class HomeController extends Controller {
	public function __construct() {
		parent::__construct();
		$this->call->model(array('Category_model', 'Product_model'));
	}

	public function index() {
		$this->call->view('pages/home', array(
			'title'      => 'Home',
			'categories' => $this->Category_model->list_all(),
			'featured'   => $this->Product_model->list_active(null, 3),
		));
	}

	public function about() {
		$this->call->view('pages/about', array('title' => 'About & Contact'));
	}
}
