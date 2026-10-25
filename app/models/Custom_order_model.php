<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Custom_order_model extends Model {
	protected $table = 'custom_order_requests';
	protected $primary_key = 'custom_order_request_id';

	private $request_select = 'SELECT r.*, u.full_name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
		FROM custom_order_requests r INNER JOIN users u ON u.user_id = r.user_id';

	public function list_for_customer($user_id) {
		return $this->db->raw($this->request_select . ' WHERE r.user_id = ? ORDER BY r.updated_at DESC, r.custom_order_request_id DESC',
			array((int) $user_id))->fetchAll();
	}

	public function list_for_staff() {
		return $this->db->raw($this->request_select . ' ORDER BY FIELD(r.status, "new", "in_review", "waiting_for_customer", "quoted", "accepted", "declined", "converted_to_order", "closed"), r.updated_at DESC')->fetchAll();
	}

	public function create_request($user_id, array $data) {
		$user_id = (int) $user_id;
		$this->db->transaction();
		do {
			$request_number = 'JKC-' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
			$exists = $this->db->raw('SELECT custom_order_request_id FROM custom_order_requests WHERE request_number = ? LIMIT 1',
				array($request_number))->fetch();
		} while ($exists);

		$request_id = $this->db->table('custom_order_requests')->insert(array(
			'request_number' => $request_number,
			'user_id' => $user_id,
			'title' => $data['title'],
			'occasion' => $data['occasion'],
			'requested_flowers' => $data['requested_flowers'],
			'colors' => $data['colors'],
			'budget' => $data['budget'],
			'recipient_name' => $data['recipient_name'],
			'delivery_address' => $data['delivery_address'],
			'requested_delivery_at' => $data['requested_delivery_at'],
			'status' => 'new',
		));
		if ( ! $request_id) {
			$this->db->roll_back();
			return false;
		}
		$this->add_message((int) $request_id, $user_id, $data['requested_flowers']);
		$this->db->commit();
		return array('custom_order_request_id' => (int) $request_id, 'request_number' => $request_number, 'status' => 'new');
	}

	public function find_for_customer($request_id, $user_id) {
		$request = $this->db->raw($this->request_select . ' WHERE r.custom_order_request_id = ? AND r.user_id = ? LIMIT 1',
			array((int) $request_id, (int) $user_id))->fetch();
		if ($request) { $request['messages'] = $this->list_messages((int) $request_id); }
		return $request;
	}

	public function find_for_staff($request_id) {
		$request = $this->db->raw($this->request_select . ' WHERE r.custom_order_request_id = ? LIMIT 1',
			array((int) $request_id))->fetch();
		if ($request) { $request['messages'] = $this->list_messages((int) $request_id); }
		return $request;
	}

	public function mark_in_review($request_id) {
		$this->db->raw("UPDATE custom_order_requests SET status = 'in_review' WHERE custom_order_request_id = ? AND status = 'new'",
			array((int) $request_id));
	}

	public function add_customer_message($request_id, $user_id, $message) {
		$this->db->transaction();
		$request = $this->db->raw('SELECT status FROM custom_order_requests
			WHERE custom_order_request_id = ? AND user_id = ? LIMIT 1 FOR UPDATE',
			array((int) $request_id, (int) $user_id))->fetch();
		if ( ! $request || in_array($request['status'], array('accepted', 'declined', 'converted_to_order', 'closed'), true)) {
			$this->db->roll_back();
			return false;
		}
		$this->add_message((int) $request_id, (int) $user_id, $message);
		$this->db->raw("UPDATE custom_order_requests SET status = 'in_review', quote_total = NULL
			WHERE custom_order_request_id = ?", array((int) $request_id));
		$this->db->commit();
		return true;
	}

	public function add_staff_message($request_id, $staff_id, $message, $quote_total = null) {
		$this->db->transaction();
		$request = $this->db->raw('SELECT status FROM custom_order_requests
			WHERE custom_order_request_id = ? LIMIT 1 FOR UPDATE', array((int) $request_id))->fetch();
		if ( ! $request || in_array($request['status'], array('declined', 'converted_to_order', 'closed'), true)) {
			$this->db->roll_back();
			return false;
		}
		$status = $quote_total === null ? 'waiting_for_customer' : 'quoted';
		$this->add_message((int) $request_id, (int) $staff_id, $message);
		$this->db->raw('UPDATE custom_order_requests SET status = ?, quote_total = ? WHERE custom_order_request_id = ?',
			array($status, $quote_total, (int) $request_id));
		$this->db->commit();
		return true;
	}

	public function customer_decision($request_id, $user_id, $accept) {
		$this->db->transaction();
		$request = $this->db->raw('SELECT status FROM custom_order_requests
			WHERE custom_order_request_id = ? AND user_id = ? LIMIT 1 FOR UPDATE',
			array((int) $request_id, (int) $user_id))->fetch();
		if ( ! $request || $request['status'] !== 'quoted') {
			$this->db->roll_back();
			return false;
		}
		$status = $accept ? 'accepted' : 'declined';
		$this->db->raw('UPDATE custom_order_requests SET status = ? WHERE custom_order_request_id = ?',
			array($status, (int) $request_id));
		$this->add_message((int) $request_id, (int) $user_id,
			$accept ? 'I accept the quoted price.' : 'I decline the quoted price.');
		$this->db->commit();
		return true;
	}

	public function convert_to_order($request_id, $staff_id) {
		$this->db->transaction();
		$request = $this->db->raw('SELECT * FROM custom_order_requests
			WHERE custom_order_request_id = ? LIMIT 1 FOR UPDATE', array((int) $request_id))->fetch();
		if ( ! $request || $request['status'] !== 'accepted' || (float) $request['quote_total'] <= 0
			|| empty($request['delivery_address']) || empty($request['requested_delivery_at'])) {
			$this->db->roll_back();
			return false;
		}
		do {
			$order_number = 'JK-' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
			$exists = $this->db->raw('SELECT order_id FROM orders WHERE order_number = ? LIMIT 1', array($order_number))->fetch();
		} while ($exists);

		$order_id = $this->db->table('orders')->insert(array(
			'order_number' => $order_number,
			'user_id' => (int) $request['user_id'],
			'status' => 'pending',
			'subtotal' => number_format((float) $request['quote_total'], 2, '.', ''),
			'delivery_fee' => '0.00',
			'total' => number_format((float) $request['quote_total'], 2, '.', ''),
			'custom_order_request_id' => (int) $request_id,
		));
		if ( ! $order_id) {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('order_status_history')->insert(array(
			'order_id' => (int) $order_id,
			'from_status' => null,
			'to_status' => 'pending',
			'changed_by' => (int) $request['user_id'],
		));
		$total = (float) $request['quote_total'];
		$deposit = round($total / 2, 2);
		$this->db->table('deliveries')->insert(array(
			'order_id' => (int) $order_id,
			'recipient_name' => $request['recipient_name'] ?: $this->customer_name((int) $request['user_id']),
			'address' => $request['delivery_address'],
			'latitude' => null,
			'longitude' => null,
			'distance_km' => null,
			'scheduled_at' => $request['requested_delivery_at'],
			'status' => 'scheduled',
			'confirmation_pin' => sprintf('%06d', random_int(0, 999999)),
		));
		$this->db->table('payments')->insert(array(
			'order_id' => (int) $order_id,
			'payment_type' => 'deposit',
			'provider' => 'cash_at_shop',
			'method' => 'cash',
			'amount' => number_format($deposit, 2, '.', ''),
			'status' => 'pending',
		));
		$this->db->table('payments')->insert(array(
			'order_id' => (int) $order_id,
			'payment_type' => 'balance',
			'provider' => 'cash_on_delivery',
			'method' => 'cash',
			'amount' => number_format($total - $deposit, 2, '.', ''),
			'status' => 'pending',
		));
		$this->db->raw("UPDATE custom_order_requests SET status = 'converted_to_order' WHERE custom_order_request_id = ?",
			array((int) $request_id));
		$this->add_message((int) $request_id, (int) $staff_id, 'The accepted quote has been converted to order ' . $order_number . '.');
		$this->db->commit();
		return array('order_id' => (int) $order_id, 'order_number' => $order_number, 'total' => number_format((float) $request['quote_total'], 2, '.', ''), 'status' => 'pending');
	}

	private function customer_name($user_id) {
		$user = $this->db->raw('SELECT full_name FROM users WHERE user_id = ? LIMIT 1', array((int) $user_id))->fetch();
		return $user ? $user['full_name'] : '';
	}

	private function add_message($request_id, $user_id, $message) {
		$this->db->table('custom_order_messages')->insert(array(
			'custom_order_request_id' => (int) $request_id,
			'user_id' => (int) $user_id,
			'message' => $message,
		));
	}

	private function list_messages($request_id) {
		return $this->db->raw('SELECT m.custom_order_message_id, m.message, m.created_at,
				u.user_id, u.full_name, r.role_name AS author_role
			FROM custom_order_messages m
			INNER JOIN users u ON u.user_id = m.user_id
			INNER JOIN roles r ON r.role_id = u.role_id
			WHERE m.custom_order_request_id = ? ORDER BY m.created_at, m.custom_order_message_id',
			array((int) $request_id))->fetchAll();
	}
}
