<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller {
	public function login() {
		$this->call->view('pages/login', array('title' => 'Login'));
	}
	public function register() {
		$this->call->view('pages/register', array('title' => 'Create Account'));
	}
}
