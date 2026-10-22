<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class OrderController extends Controller {
	public function __construct() {
		parent::__construct();
		$this->call->model('Order_model');
	}

	/** /track  or  /track?order=BE-0001 */
	public function track() {
		$number = isset($_GET['order']) ? strtoupper(trim($_GET['order'])) : '';
		$data = array('title' => 'Track Order', 'number' => $number, 'order' => null, 'items' => array(), 'delivery' => null);
		if ($number !== '') {
			$order = $this->Order_model->find_by_number($number);
			if ($order) {
				$data['order']    = $order;
				$data['items']    = $this->Order_model->list_items($order['order_id']);
				$data['delivery'] = $this->Order_model->find_delivery($order['order_id']);
			}
		}
		$this->call->view('pages/track', $data);
	}

	public function history() {
		$this->call->view('pages/history', array(
			'title'  => 'My Orders',
			'orders' => $this->Order_model->list_for_user(demo_customer_id()),
		));
	}
}
