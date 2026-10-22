<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Order_model extends Model {
	protected $table = 'orders';
	protected $primary_key = 'order_id';

	private $base = 'SELECT o.*, u.full_name AS customer, u.email AS customer_email, d.status AS delivery_status
		FROM orders o
		INNER JOIN users u ON u.user_id = o.user_id
		LEFT JOIN deliveries d ON d.order_id = o.order_id';

	public function list_all() {
		return $this->db->raw($this->base . ' ORDER BY o.created_at DESC, o.order_id DESC')->fetchAll();
	}

	public function list_recent($limit = 5) {
		return $this->db->raw($this->base . ' ORDER BY o.created_at DESC, o.order_id DESC LIMIT ' . (int) $limit)->fetchAll();
	}

	public function list_for_user($user_id) {
		return $this->db->raw($this->base . ' WHERE o.user_id = ? ORDER BY o.created_at DESC, o.order_id DESC', array($user_id))->fetchAll();
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
}
