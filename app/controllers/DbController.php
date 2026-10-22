<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * /db-check : shows that the database connection works and how many
 * records each table holds. Useful for the Checking 2 demonstration.
 */
class DbController extends Controller {
	public function check() {
		$cfg = database_config()['main'];
		$tables = array('roles', 'users', 'categories', 'products', 'addons', 'carts', 'cart_items', 'orders',
			'order_items', 'order_item_addons', 'deliveries', 'payments', 'stock_logs', 'notifications');
		$counts = array();
		foreach ($tables as $t) {
			$counts[$t] = (int) $this->db->table($t)->count();
		}
		$this->call->view('pages/db_check', array(
			'title'  => 'Database Check',
			'host'   => $cfg['hostname'],
			'dbname' => $cfg['database'],
			'driver' => $cfg['driver'],
			'counts' => $counts,
		));
	}
}
