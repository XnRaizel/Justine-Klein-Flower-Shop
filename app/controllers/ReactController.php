<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ReactController extends Controller {
	public function app() {
		$this->call->view('react_app', array('title' => 'Justine Klein Flower Shop'));
	}
}
