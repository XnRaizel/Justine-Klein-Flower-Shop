<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Delivery_model extends Model {
	protected $table = 'deliveries';
	protected $primary_key = 'delivery_id';

	public function list_all() {
		return $this->db->raw('SELECT d.delivery_id, d.order_id, d.recipient_name, d.address,
				d.latitude, d.longitude, d.distance_km, d.scheduled_at, d.status, d.assigned_to,
				d.delivered_at, d.delivered_to, d.confirmed_by, d.operations_archived_at, r.full_name AS rider,
				cf.full_name AS confirmed_by_name, o.order_number,
				o.status AS order_status,
				(SELECT p.amount FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_amount,
				(SELECT p.status FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_status,
				(SELECT p.amount FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'balance\' LIMIT 1) AS balance_amount,
				(SELECT p.status FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'balance\' LIMIT 1) AS balance_status
			FROM deliveries d
			INNER JOIN orders o ON o.order_id = d.order_id
			LEFT JOIN users r ON r.user_id = d.assigned_to
			LEFT JOIN users cf ON cf.user_id = d.confirmed_by
			ORDER BY d.scheduled_at DESC, d.delivery_id DESC')->fetchAll();
	}

	public function list_assigned_to_rider($rider_id) {
		return $this->db->raw("SELECT d.delivery_id, d.order_id, d.recipient_name, d.address, d.scheduled_at,
				d.status AS delivery_status, o.order_number, o.status AS order_status,
				u.phone AS customer_phone,
				(SELECT p.amount FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = 'balance' LIMIT 1) AS balance_amount,
				(SELECT p.status FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = 'balance' LIMIT 1) AS balance_status
			FROM deliveries d INNER JOIN orders o ON o.order_id = d.order_id
			INNER JOIN users u ON u.user_id = o.user_id
			WHERE d.assigned_to = ? AND d.status = 'out_for_delivery' AND o.status = 'out_for_delivery'
			ORDER BY d.scheduled_at DESC, d.delivery_id DESC", array((int) $rider_id))->fetchAll();
	}

	public function set_operations_archived($delivery_id, $archived) {
		$this->db->transaction();
		$delivery = $this->db->raw('SELECT d.status AS delivery_status, o.status AS order_status
			FROM deliveries d INNER JOIN orders o ON o.order_id = d.order_id
			WHERE d.delivery_id = ? LIMIT 1 FOR UPDATE', array((int) $delivery_id))->fetch();
		if ( ! $delivery || ! in_array($delivery['delivery_status'], array('delivered', 'cancelled', 'failed'), true)
			|| ! in_array($delivery['order_status'], array('delivered', 'cancelled'), true)) {
			$this->db->roll_back();
			return false;
		}
		$archived_at = $archived ? date('Y-m-d H:i:s') : null;
		$this->db->table('deliveries')->where(array('delivery_id' => (int) $delivery_id))
			->update(array('operations_archived_at' => $archived_at));
		$this->db->commit();
		return array('archived_at' => $archived_at);
	}

	public function assign_rider($delivery_id, $staff_id) {
		$this->db->transaction();
		$delivery = $this->db->raw("SELECT d.status, o.status AS order_status FROM deliveries d
			INNER JOIN orders o ON o.order_id = d.order_id WHERE d.delivery_id = ? LIMIT 1 FOR UPDATE",
			array((int) $delivery_id))->fetch();
		if ( ! $delivery || ! in_array($delivery['order_status'], array('confirmed', 'preparing'), true)
			|| ! in_array($delivery['status'], array('scheduled', 'preparing'), true)) {
			$this->db->roll_back();
			return false;
		}
		$rider = $this->db->raw("SELECT u.user_id FROM users u INNER JOIN roles r ON r.role_id = u.role_id
			WHERE u.user_id = ? AND r.role_name = 'rider' LIMIT 1 FOR UPDATE", array((int) $staff_id))->fetch();
		if ( ! $rider) {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('deliveries')->where(array('delivery_id' => (int) $delivery_id))
			->update(array('assigned_to' => (int) $staff_id));
		$this->db->commit();
		return true;
	}

	public function confirm_delivery($delivery_id, $rider_id, $pin, $delivered_to, $cash_collected) {
		$pin = trim((string) $pin);
		$delivered_to = trim((string) $delivered_to);
		if ( ! preg_match('/^\d{6}$/', $pin) || $delivered_to === '' || strlen($delivered_to) > 100) {
			return false;
		}
		$this->db->transaction();
		$delivery = $this->db->raw("SELECT d.delivery_id, d.order_id, d.status AS delivery_status,
				d.assigned_to, d.confirmation_pin, o.status AS order_status
			FROM deliveries d INNER JOIN orders o ON o.order_id = d.order_id
			WHERE d.delivery_id = ? LIMIT 1 FOR UPDATE", array((int) $delivery_id))->fetch();
		if ( ! $delivery || (int) $delivery['assigned_to'] !== (int) $rider_id
			|| $delivery['delivery_status'] !== 'out_for_delivery'
			|| $delivery['order_status'] !== 'out_for_delivery'
			|| ! hash_equals((string) $delivery['confirmation_pin'], $pin)) {
			$this->db->roll_back();
			return false;
		}
		$balance = $this->db->raw("SELECT payment_id, status FROM payments
			WHERE order_id = ? AND payment_type = 'balance' LIMIT 1 FOR UPDATE",
			array((int) $delivery['order_id']))->fetch();
		if ( ! $balance || ! in_array($balance['status'], array('pending', 'paid'), true)
			|| ($balance['status'] === 'pending' && ! $cash_collected)) {
			$this->db->roll_back();
			return false;
		}
		$now = date('Y-m-d H:i:s');
		if ($balance['status'] === 'pending') {
			$this->db->table('payments')->where(array('payment_id' => (int) $balance['payment_id']))
				->update(array('status' => 'paid', 'paid_at' => $now, 'paid_by' => (int) $rider_id));
		}
		$this->db->table('orders')->where(array('order_id' => (int) $delivery['order_id']))
			->update(array('status' => 'delivered'));
		$this->db->table('deliveries')->where(array('delivery_id' => (int) $delivery_id))->update(array(
			'status' => 'delivered',
			'delivered_at' => $now,
			'delivered_to' => $delivered_to,
			'confirmed_by' => (int) $rider_id,
			'confirmation_pin' => null,
		));
		$this->db->table('order_status_history')->insert(array(
			'order_id' => (int) $delivery['order_id'],
			'from_status' => 'out_for_delivery',
			'to_status' => 'delivered',
			'changed_by' => (int) $rider_id,
		));
		$this->db->commit();
		return array('delivery_id' => (int) $delivery_id, 'delivered_at' => $now, 'delivered_to' => $delivered_to);
	}
}
