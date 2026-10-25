<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Order_model extends Model {
	protected $table = 'orders';
	protected $primary_key = 'order_id';

	private $base = 'SELECT o.*, u.full_name AS customer, u.email AS customer_email, d.status AS delivery_status,
			d.assigned_to, d.delivered_at, d.delivered_to, d.confirmed_by,
			confirmer.full_name AS confirmed_by_name,
			cr.request_number AS custom_request_number, cr.title AS custom_order_title, cr.requested_flowers AS custom_order_details,
			(SELECT p.amount FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_amount,
			(SELECT p.status FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_status,
			(SELECT p.method FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS payment_method,
			(SELECT p.provider_ref IS NOT NULL FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_session_exists,
			(SELECT p.amount FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'balance\' LIMIT 1) AS balance_amount,
			(SELECT p.status FROM payments p WHERE p.order_id = o.order_id AND p.payment_type = \'balance\' LIMIT 1) AS balance_status,
			(SELECT COALESCE(SUM(r.amount), 0) FROM payment_refunds r
				INNER JOIN payments p ON p.payment_id = r.payment_id
				WHERE p.order_id = o.order_id AND p.payment_type = \'deposit\') AS deposit_refunded
		FROM orders o
		INNER JOIN users u ON u.user_id = o.user_id
		LEFT JOIN deliveries d ON d.order_id = o.order_id
		LEFT JOIN users confirmer ON confirmer.user_id = d.confirmed_by
		LEFT JOIN custom_order_requests cr ON cr.custom_order_request_id = o.custom_order_request_id';

	public function list_all() {
		return $this->db->raw('SELECT q.*,
				(SELECT u.full_name FROM payments p INNER JOIN users u ON u.user_id = p.paid_by
					WHERE p.order_id = q.order_id AND p.payment_type = \'deposit\' LIMIT 1) AS deposit_received_by,
				(SELECT u.full_name FROM payments p INNER JOIN users u ON u.user_id = p.paid_by
					WHERE p.order_id = q.order_id AND p.payment_type = \'balance\' LIMIT 1) AS balance_received_by
			FROM (' . $this->base . ') q ORDER BY q.created_at DESC, q.order_id DESC')->fetchAll();
	}

	public function list_recent($limit = 5) {
		return $this->db->raw($this->base . ' ORDER BY o.created_at DESC, o.order_id DESC LIMIT ' . (int) $limit)->fetchAll();
	}

	public function list_for_user($user_id) {
		return $this->db->raw('SELECT q.*,
				CASE WHEN q.status = \'out_for_delivery\' AND q.delivery_status = \'out_for_delivery\'
					THEN d.confirmation_pin ELSE NULL END AS delivery_pin
			FROM (' . $this->base . ') q
			LEFT JOIN deliveries d ON d.order_id = q.order_id
			WHERE q.user_id = ? ORDER BY q.created_at DESC, q.order_id DESC', array((int) $user_id))->fetchAll();
	}

	public function set_customer_hidden($user_id, $order_id, $hidden) {
		$this->db->transaction();
		$order = $this->db->raw('SELECT status FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1 FOR UPDATE',
			array((int) $order_id, (int) $user_id))->fetch();
		if ( ! $order || ! in_array($order['status'], array('delivered', 'cancelled'), true)) {
			$this->db->roll_back();
			return false;
		}
		$hidden_at = $hidden ? date('Y-m-d H:i:s') : null;
		$this->db->table('orders')->where(array('order_id' => (int) $order_id))
			->update(array('customer_hidden_at' => $hidden_at));
		$this->db->commit();
		return array('hidden_at' => $hidden_at);
	}

	public function set_operations_archived($order_id, $archived) {
		$this->db->transaction();
		$order = $this->db->raw('SELECT status FROM orders WHERE order_id = ? LIMIT 1 FOR UPDATE',
			array((int) $order_id))->fetch();
		if ( ! $order || ! in_array($order['status'], array('delivered', 'cancelled'), true)) {
			$this->db->roll_back();
			return false;
		}
		$archived_at = $archived ? date('Y-m-d H:i:s') : null;
		$this->db->table('orders')->where(array('order_id' => (int) $order_id))
			->update(array('operations_archived_at' => $archived_at));
		$this->db->commit();
		return array('archived_at' => $archived_at);
	}

	public function find_by_number($order_number) {
		return $this->db->raw($this->base . ' WHERE o.order_number = ?', array($order_number))->fetch();
	}

	/** Items of an order, each with its chosen add-ons. */
	public function list_items($order_id) {
		$items = $this->db->raw('SELECT oi.*, p.name AS product_name
			FROM order_items oi INNER JOIN products p ON p.product_id = oi.product_id
			WHERE oi.order_id = ? ORDER BY oi.order_item_id', array($order_id))->fetchAll();
		foreach ($items as $k => $it) {
			$items[$k]['addons'] = $this->db->raw('SELECT a.name, oia.price
				FROM order_item_addons oia INNER JOIN addons a ON a.addon_id = oia.addon_id
				WHERE oia.order_item_id = ?', array($it['order_item_id']))->fetchAll();
		}
		return $items;
	}

	public function find_delivery($order_id) {
		return $this->db->raw('SELECT d.*, r.full_name AS rider
			FROM deliveries d LEFT JOIN users r ON r.user_id = d.assigned_to
			WHERE d.order_id = ?', array($order_id))->fetch();
	}

	public function count_by_status($status) {
		return (int) $this->db->table('orders')->where('status', $status)->count();
	}

	/** Total sales (cancelled orders excluded) since a date-time string. */
	public function sales_since($datetime) {
		$row = $this->db->raw("SELECT COALESCE(SUM(total), 0) AS sales FROM orders WHERE status <> 'cancelled' AND created_at >= ?", array($datetime))->fetch();
		return (float) $row['sales'];
	}

	/** Orders and sales per day, newest first. */
	public function report_by_day($since) {
		return $this->db->raw("SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS sales
			FROM orders WHERE status <> 'cancelled' AND created_at >= ?
			GROUP BY DATE(created_at) ORDER BY day DESC", array($since))->fetchAll();
	}

	/** Best selling products (cancelled orders excluded). */
	public function report_top_products($limit = 5) {
		return $this->db->raw("SELECT p.name, SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.unit_price) AS revenue
			FROM order_items oi
			INNER JOIN orders o ON o.order_id = oi.order_id
			INNER JOIN products p ON p.product_id = oi.product_id
			WHERE o.status <> 'cancelled'
			GROUP BY p.product_id, p.name ORDER BY qty DESC, revenue DESC LIMIT " . (int) $limit)->fetchAll();
	}

	public function create_from_cart($user_id, array $delivery, $payment_method) {
		if ( ! in_array($payment_method, array('cash_at_shop', 'paymongo'), true)) {
			return false;
		}
		$user_id = (int) $user_id;
		$this->db->transaction();
		$rows = $this->db->raw('SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.customization,
				p.name, p.price, p.stock_qty, p.is_archived
			FROM cart_items ci INNER JOIN carts c ON c.cart_id = ci.cart_id
			INNER JOIN products p ON p.product_id = ci.product_id
			WHERE c.user_id = ? ORDER BY p.product_id, ci.cart_item_id FOR UPDATE', array($user_id))->fetchAll();
		if ( ! $rows) {
			$this->db->roll_back();
			return false;
		}

		$required_stock = array();
		$items = array();
		$subtotal = 0.0;
		foreach ($rows as $row) {
			$product_id = (int) $row['product_id'];
			$quantity = (int) $row['quantity'];
			if ((int) $row['is_archived'] || $quantity < 1) {
				$this->db->roll_back();
				return false;
			}
			$required_stock[$product_id] = isset($required_stock[$product_id])
				? $required_stock[$product_id] + $quantity
				: $quantity;
			if ($required_stock[$product_id] > (int) $row['stock_qty']) {
				$this->db->roll_back();
				return false;
			}

			$customization = json_decode((string) $row['customization'], true);
			$addon_ids = isset($customization['addon_ids']) && is_array($customization['addon_ids'])
				? array_values(array_unique(array_filter(array_map('intval', $customization['addon_ids']))))
				: array();
			$addons = array();
			if ($addon_ids) {
				$marks = implode(',', array_fill(0, count($addon_ids), '?'));
				$addons = $this->db->raw('SELECT addon_id, price FROM addons WHERE addon_id IN (' . $marks . ')', $addon_ids)->fetchAll();
				if (count($addons) !== count($addon_ids)) {
					$this->db->roll_back();
					return false;
				}
			}
			$subtotal += (float) $row['price'] * $quantity;
			foreach ($addons as $addon) { $subtotal += (float) $addon['price'] * $quantity; }
			$items[] = array(
				'product_id' => $product_id,
				'quantity' => $quantity,
				'unit_price' => number_format((float) $row['price'], 2, '.', ''),
				'addons' => $addons,
			);
		}

		foreach ($required_stock as $product_id => $quantity) {
			$updated = $this->db->raw('UPDATE products SET stock_qty = stock_qty - ?
				WHERE product_id = ? AND is_archived = 0 AND stock_qty >= ?',
				array($quantity, $product_id, $quantity));
			if ($updated->rowCount() !== 1) {
				$this->db->roll_back();
				return false;
			}
		}

		$delivery_fee = 0.0;
		do {
			$order_number = 'JK-' . date('ymd') . strtoupper(bin2hex(random_bytes(5)));
			$duplicate = $this->db->raw('SELECT order_id FROM orders WHERE order_number = ? LIMIT 1', array($order_number))->fetch();
		} while ($duplicate);
		$total = $subtotal + $delivery_fee;
		$deposit_amount = round($total / 2, 2);
		$balance_amount = $total - $deposit_amount;
		$order_id = $this->db->table('orders')->insert(array(
			'order_number' => $order_number,
			'user_id' => $user_id,
			'status' => 'pending',
			'subtotal' => number_format($subtotal, 2, '.', ''),
			'delivery_fee' => number_format($delivery_fee, 2, '.', ''),
			'total' => number_format($subtotal + $delivery_fee, 2, '.', ''),
			'card_message' => $delivery['card_message'],
		));
		if ( ! $order_id) {
			$this->db->roll_back();
			return false;
		}
		$this->record_status_change((int) $order_id, null, 'pending', $user_id);

		foreach ($items as $item) {
			$order_item_id = $this->db->table('order_items')->insert(array(
				'order_id' => (int) $order_id,
				'product_id' => $item['product_id'],
				'quantity' => $item['quantity'],
				'unit_price' => $item['unit_price'],
			));
			foreach ($item['addons'] as $addon) {
				$this->db->table('order_item_addons')->insert(array(
					'order_item_id' => (int) $order_item_id,
					'addon_id' => (int) $addon['addon_id'],
					'price' => number_format((float) $addon['price'], 2, '.', ''),
				));
			}
		}

		$this->db->table('deliveries')->insert(array(
			'order_id' => (int) $order_id,
			'recipient_name' => $delivery['recipient_name'],
			'address' => $delivery['address'],
			'latitude' => null,
			'longitude' => null,
			'distance_km' => null,
			'scheduled_at' => $delivery['scheduled_at'],
			'status' => 'scheduled',
			'confirmation_pin' => $this->new_delivery_pin(),
		));
		$this->db->table('payments')->insert(array(
			'order_id' => (int) $order_id,
			'payment_type' => 'deposit',
			'provider' => $payment_method === 'paymongo' ? 'paymongo' : 'cash_at_shop',
			'method' => $payment_method === 'paymongo' ? 'online' : 'cash',
			'amount' => number_format($deposit_amount, 2, '.', ''),
			'status' => 'pending',
		));
		$this->db->table('payments')->insert(array(
			'order_id' => (int) $order_id,
			'payment_type' => 'balance',
			'provider' => 'cash_on_delivery',
			'method' => 'cash',
			'amount' => number_format($balance_amount, 2, '.', ''),
			'status' => 'pending',
		));
		foreach ($required_stock as $product_id => $quantity) {
			$this->db->table('stock_logs')->insert(array(
				'product_id' => (int) $product_id,
				'order_id' => (int) $order_id,
				'user_id' => $user_id,
				'change_qty' => -1 * (int) $quantity,
				'reason' => 'order_placed',
			));
		}
		$this->db->raw('DELETE ci FROM cart_items ci INNER JOIN carts c ON c.cart_id = ci.cart_id WHERE c.user_id = ?', array($user_id));
		$this->db->commit();

		return array(
			'order_id' => (int) $order_id,
			'order_number' => $order_number,
			'subtotal' => number_format($subtotal, 2, '.', ''),
			'delivery_fee' => number_format($delivery_fee, 2, '.', ''),
			'total' => number_format($total, 2, '.', ''),
			'deposit_amount' => number_format($deposit_amount, 2, '.', ''),
			'balance_amount' => number_format($balance_amount, 2, '.', ''),
			'payment_method' => $payment_method,
			'status' => 'pending',
		);
	}

	public function find_pending_online_deposit($user_id, $order_id) {
		return $this->db->raw('SELECT o.order_id, o.order_number, o.total, u.full_name, u.email,
				p.payment_id, p.amount, p.status AS payment_status, p.provider_ref, p.provider_url
			FROM orders o
			INNER JOIN users u ON u.user_id = o.user_id
			INNER JOIN payments p ON p.order_id = o.order_id AND p.payment_type = \'deposit\'
			WHERE o.order_id = ? AND o.user_id = ? AND o.status = \'pending\'
				AND p.provider = \'paymongo\' AND p.method = \'online\'
			LIMIT 1', array((int) $order_id, (int) $user_id))->fetch();
	}

	public function save_paymongo_session($payment_id, $session_id, $checkout_url) {
		$this->db->table('payments')->where(array('payment_id' => (int) $payment_id))->update(array(
			'provider_ref' => $session_id,
			'provider_url' => $checkout_url,
		));
	}

	public function mark_cash_deposit_paid($order_id, $staff_id) {
		$this->db->transaction();
		$order = $this->db->raw('SELECT order_id, status FROM orders WHERE order_id = ? LIMIT 1 FOR UPDATE',
			array((int) $order_id))->fetch();
		$payment = $this->db->raw('SELECT payment_id, status FROM payments
			WHERE order_id = ? AND payment_type = \'deposit\' AND provider = \'cash_at_shop\'
			LIMIT 1 FOR UPDATE', array((int) $order_id))->fetch();
		if ( ! $order || $order['status'] !== 'pending' || ! $payment || $payment['status'] !== 'pending') {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('payments')->where(array('payment_id' => (int) $payment['payment_id']))->update(array(
			'status' => 'paid',
			'paid_at' => date('Y-m-d H:i:s'),
			'paid_by' => (int) $staff_id,
		));
		$this->db->table('orders')->where(array('order_id' => (int) $order_id))->update(array('status' => 'confirmed'));
		$this->record_status_change((int) $order_id, 'pending', 'confirmed', (int) $staff_id);
		$this->db->commit();
		return true;
	}

	public function advance_status($order_id, $next_status, $staff_id) {
		$transitions = array(
			'confirmed' => 'preparing',
			'preparing' => 'out_for_delivery',
		);
		$this->db->transaction();
		$order = $this->db->raw('SELECT o.status, d.delivery_id, d.status AS delivery_status, d.assigned_to
			FROM orders o LEFT JOIN deliveries d ON d.order_id = o.order_id
			WHERE o.order_id = ? LIMIT 1 FOR UPDATE', array((int) $order_id))->fetch();
		if ( ! $order || ! isset($transitions[$order['status']]) || $transitions[$order['status']] !== $next_status
			|| ! $order['delivery_id'] || in_array($order['delivery_status'], array('cancelled', 'delivered'), true)) {
			$this->db->roll_back();
			return false;
		}
		if ($next_status === 'preparing') {
			$deposit = $this->db->raw("SELECT status FROM payments WHERE order_id = ? AND payment_type = 'deposit' LIMIT 1 FOR UPDATE",
				array((int) $order_id))->fetch();
			if ( ! $deposit || $deposit['status'] !== 'paid') {
				$this->db->roll_back();
				return false;
			}
		}
		if ($next_status === 'out_for_delivery' && ! $order['assigned_to']) {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('orders')->where(array('order_id' => (int) $order_id))->update(array('status' => $next_status));
		$delivery_update = array('status' => $next_status);
		$this->db->table('deliveries')->where(array('delivery_id' => (int) $order['delivery_id']))->update($delivery_update);
		$this->record_status_change((int) $order_id, $order['status'], $next_status, (int) $staff_id);
		$this->db->commit();
		return array('status' => $next_status, 'delivery_status' => $next_status);
	}

	public function mark_cash_balance_paid($order_id, $staff_id) {
		$this->db->transaction();
		$order = $this->db->raw('SELECT status FROM orders WHERE order_id = ? LIMIT 1 FOR UPDATE',
			array((int) $order_id))->fetch();
		$payment = $this->db->raw("SELECT payment_id, status FROM payments
			WHERE order_id = ? AND payment_type = 'balance' AND provider = 'cash_on_delivery'
			LIMIT 1 FOR UPDATE", array((int) $order_id))->fetch();
		if ( ! $order || $order['status'] !== 'out_for_delivery' || ! $payment || $payment['status'] !== 'pending') {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('payments')->where(array('payment_id' => (int) $payment['payment_id']))->update(array(
			'status' => 'paid',
			'paid_at' => date('Y-m-d H:i:s'),
			'paid_by' => (int) $staff_id,
		));
		$this->db->commit();
		return true;
	}

	public function record_deposit_refund($order_id, $staff_id, $amount) {
		if ( ! is_numeric($amount) || (float) $amount <= 0) { return false; }
		$raw_amount = (float) $amount;
		$amount = round($raw_amount, 2);
		if (abs($raw_amount - $amount) > 0.000001) { return false; }
		$this->db->transaction();
		$order = $this->db->raw('SELECT status FROM orders WHERE order_id = ? LIMIT 1 FOR UPDATE',
			array((int) $order_id))->fetch();
		$payment = $this->db->raw("SELECT payment_id, amount, status FROM payments
			WHERE order_id = ? AND payment_type = 'deposit' LIMIT 1 FOR UPDATE", array((int) $order_id))->fetch();
		if ( ! $order || $order['status'] !== 'cancelled' || ! $payment
			|| ! in_array($payment['status'], array('paid', 'partially_refunded'), true)) {
			$this->db->roll_back();
			return false;
		}
		$refunded = $this->db->raw('SELECT COALESCE(SUM(amount), 0) AS amount
			FROM payment_refunds WHERE payment_id = ?', array((int) $payment['payment_id']))->fetch();
		$remaining_cents = (int) round(((float) $payment['amount'] - (float) $refunded['amount']) * 100);
		$amount_cents = (int) round($amount * 100);
		if ($amount_cents > $remaining_cents) {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('payment_refunds')->insert(array(
			'payment_id' => (int) $payment['payment_id'],
			'amount' => number_format($amount, 2, '.', ''),
			'recorded_by' => (int) $staff_id,
		));
		$this->db->table('payments')->where(array('payment_id' => (int) $payment['payment_id']))
			->update(array('status' => $amount_cents === $remaining_cents ? 'refunded' : 'partially_refunded'));
		$this->db->commit();
		return array(
			'refunded_amount' => number_format((float) $refunded['amount'] + $amount, 2, '.', ''),
			'refund_status' => $amount_cents === $remaining_cents ? 'refunded' : 'partially_refunded',
		);
	}

	public function list_status_history($order_id) {
		return $this->db->raw('SELECT h.from_status, h.to_status, h.changed_at, u.full_name AS changed_by
			FROM order_status_history h INNER JOIN users u ON u.user_id = h.changed_by
			WHERE h.order_id = ? ORDER BY h.history_id', array((int) $order_id))->fetchAll();
	}

	private function record_status_change($order_id, $from_status, $to_status, $user_id) {
		$this->db->table('order_status_history')->insert(array(
			'order_id' => (int) $order_id,
			'from_status' => $from_status,
			'to_status' => $to_status,
			'changed_by' => (int) $user_id,
		));
	}

	private function new_delivery_pin() {
		return sprintf('%06d', random_int(0, 999999));
	}

	public function mark_paymongo_deposit_paid($order_number, $session_id, $amount_cents) {
		$this->db->transaction();
		$order = $this->db->raw('SELECT o.order_id, o.user_id, o.status, p.payment_id, p.status AS payment_status,
				p.provider_ref, p.amount
			FROM orders o INNER JOIN payments p ON p.order_id = o.order_id AND p.payment_type = \'deposit\'
			WHERE o.order_number = ? AND p.provider = \'paymongo\' AND p.method = \'online\'
			LIMIT 1 FOR UPDATE', array($order_number))->fetch();
		if ( ! $order || ! hash_equals((string) $order['provider_ref'], (string) $session_id)
			|| (int) round((float) $order['amount'] * 100) !== (int) $amount_cents) {
			$this->db->roll_back();
			return false;
		}
		if ($order['payment_status'] === 'paid') {
			$this->db->commit();
			return true;
		}
		if ($order['status'] !== 'pending' || $order['payment_status'] !== 'pending') {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('payments')->where(array('payment_id' => (int) $order['payment_id']))->update(array(
			'status' => 'paid',
			'paid_at' => date('Y-m-d H:i:s'),
		));
		$this->db->table('orders')->where(array('order_id' => (int) $order['order_id']))->update(array('status' => 'confirmed'));
		$this->record_status_change((int) $order['order_id'], 'pending', 'confirmed', (int) $order['user_id']);
		$this->db->commit();
		return true;
	}

	public function cancel_before_delivery($user_id, $order_id) {
		$user_id = (int) $user_id;
		$order_id = (int) $order_id;
		$this->db->transaction();
		$order = $this->db->raw('SELECT o.order_id, o.order_number, o.status, d.status AS delivery_status
			FROM orders o LEFT JOIN deliveries d ON d.order_id = o.order_id
			WHERE o.order_id = ? AND o.user_id = ? LIMIT 1 FOR UPDATE', array($order_id, $user_id))->fetch();
		$cancellable_statuses = array('pending', 'confirmed', 'preparing');
		if ( ! $order || ! in_array($order['status'], $cancellable_statuses, true)
			|| in_array($order['delivery_status'], array('out_for_delivery', 'delivered'), true)) {
			$this->db->roll_back();
			return false;
		}
		$unpaid_online_session = $this->db->raw("SELECT payment_id FROM payments
			WHERE order_id = ? AND payment_type = 'deposit' AND provider = 'paymongo'
				AND provider_ref IS NOT NULL AND status <> 'paid'
			LIMIT 1 FOR UPDATE", array($order_id))->fetch();
		if ($unpaid_online_session) {
			$this->db->roll_back();
			return false;
		}
		$items = $this->db->raw('SELECT product_id, SUM(quantity) AS quantity FROM order_items
			WHERE order_id = ? GROUP BY product_id', array($order_id))->fetchAll();
		foreach ($items as $item) {
			$this->db->raw('UPDATE products SET stock_qty = stock_qty + ? WHERE product_id = ?',
				array((int) $item['quantity'], (int) $item['product_id']));
			$this->db->table('stock_logs')->insert(array(
				'product_id' => (int) $item['product_id'],
				'order_id' => $order_id,
				'user_id' => $user_id,
				'change_qty' => (int) $item['quantity'],
				'reason' => 'order_cancelled',
			));
		}
		$this->db->table('orders')->where(array('order_id' => $order_id))->update(array('status' => 'cancelled'));
		$this->db->table('deliveries')->where(array('order_id' => $order_id))->update(array('status' => 'cancelled'));
		$this->db->raw("UPDATE payments SET status = 'failed' WHERE order_id = ? AND status = 'pending'", array($order_id));
		$this->record_status_change($order_id, $order['status'], 'cancelled', $user_id);
		$this->db->commit();
		return array('order_id' => $order_id, 'order_number' => $order['order_number'], 'status' => 'cancelled');
	}
}
