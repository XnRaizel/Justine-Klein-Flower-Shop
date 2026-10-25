<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Cart_model extends Model {
	protected $table = 'carts';
	protected $primary_key = 'cart_id';

	public function list_items($user_id) {
		return $this->db->raw('SELECT ci.cart_item_id, ci.quantity, ci.customization, p.product_id, p.name, p.price,
				(ci.quantity * p.price) AS line_total
			FROM cart_items ci
			INNER JOIN carts c ON c.cart_id = ci.cart_id
			INNER JOIN products p ON p.product_id = ci.product_id
			WHERE c.user_id = ? ORDER BY ci.cart_item_id', array($user_id))->fetchAll();
	}

	public function get_cart($user_id) {
		$rows = $this->db->raw('SELECT ci.cart_item_id, ci.product_id, ci.quantity, ci.customization,
				p.name, p.price, p.stock_qty, p.image
			FROM cart_items ci INNER JOIN carts c ON c.cart_id = ci.cart_id
			INNER JOIN products p ON p.product_id = ci.product_id
			WHERE c.user_id = ? ORDER BY ci.cart_item_id', array((int) $user_id))->fetchAll();
		$items = array();
		$total = 0.0;
		foreach ($rows as $row) {
			$customization = json_decode((string) $row['customization'], true);
			$addon_ids = isset($customization['addon_ids']) && is_array($customization['addon_ids'])
				? array_values(array_unique(array_filter(array_map('intval', $customization['addon_ids']))))
				: array();
			$addons = array();
			if ($addon_ids) {
				$marks = implode(',', array_fill(0, count($addon_ids), '?'));
				$addons = $this->db->raw('SELECT addon_id, name, price FROM addons WHERE addon_id IN (' . $marks . ') ORDER BY addon_id', $addon_ids)->fetchAll();
			}
			$addon_total = 0.0;
			foreach ($addons as $addon) { $addon_total += (float) $addon['price']; }
			$unit_total = (float) $row['price'] + $addon_total;
			$line_total = $unit_total * (int) $row['quantity'];
			$total += $line_total;
			$items[] = array(
				'cart_item_id' => (int) $row['cart_item_id'],
				'product_id' => (int) $row['product_id'],
				'name' => $row['name'],
				'image' => $row['image'],
				'quantity' => (int) $row['quantity'],
				'stock_qty' => (int) $row['stock_qty'],
				'unit_price' => number_format($unit_total, 2, '.', ''),
				'line_total' => number_format($line_total, 2, '.', ''),
				'addons' => $addons,
			);
		}
		return array('items' => $items, 'total' => number_format($total, 2, '.', ''));
	}

	public function add_item($user_id, $product_id, $quantity, array $addon_ids) {
		$user_id = (int) $user_id;
		$product_id = (int) $product_id;
		$quantity = (int) $quantity;
		$this->db->transaction();
		$product = $this->db->raw('SELECT stock_qty FROM products WHERE product_id = ? AND is_archived = 0 FOR UPDATE', array($product_id))->fetch();
		if ( ! $product) {
			$this->db->roll_back();
			return false;
		}
		$cart = $this->db->raw('SELECT cart_id FROM carts WHERE user_id = ? ORDER BY cart_id ASC LIMIT 1', array($user_id))->fetch();
		$in_cart = $this->db->raw('SELECT COALESCE(SUM(ci.quantity), 0) AS quantity
			FROM cart_items ci INNER JOIN carts c ON c.cart_id = ci.cart_id
			WHERE c.user_id = ? AND ci.product_id = ?', array($user_id, $product_id))->fetch();
		if ((int) $in_cart['quantity'] + $quantity > (int) $product['stock_qty']) {
			$this->db->roll_back();
			return false;
		}
		if ( ! $cart) {
			$cart_id = $this->db->table('carts')->insert(array('user_id' => $user_id));
		} else {
			$cart_id = (int) $cart['cart_id'];
		}

		$customization = json_encode(array('addon_ids' => array_values($addon_ids)));
		$this->db->table('cart_items')->insert(array(
			'cart_id' => $cart_id,
			'product_id' => $product_id,
			'quantity' => $quantity,
			'customization' => $customization,
		));
		$this->db->commit();
		return true;
	}

	public function update_item($user_id, $item_id, $quantity) {
		$this->db->transaction();
		$item = $this->db->raw('SELECT ci.cart_id, ci.product_id, p.stock_qty
			FROM cart_items ci INNER JOIN carts c ON c.cart_id = ci.cart_id
			INNER JOIN products p ON p.product_id = ci.product_id
			WHERE c.user_id = ? AND ci.cart_item_id = ? LIMIT 1 FOR UPDATE', array((int) $user_id, (int) $item_id))->fetch();
		if ( ! $item) { $this->db->roll_back(); return false; }
		$other = $this->db->raw('SELECT COALESCE(SUM(ci.quantity), 0) AS quantity FROM cart_items ci
			INNER JOIN carts c ON c.cart_id = ci.cart_id
			WHERE c.user_id = ? AND ci.product_id = ? AND ci.cart_item_id <> ?',
			array((int) $user_id, (int) $item['product_id'], (int) $item_id))->fetch();
		if ((int) $other['quantity'] + (int) $quantity > (int) $item['stock_qty']) {
			$this->db->roll_back();
			return false;
		}
		$this->db->table('cart_items')->where(array('cart_item_id' => (int) $item_id))->update(array('quantity' => (int) $quantity));
		$this->db->commit();
		return true;
	}

	public function remove_item($user_id, $item_id) {
		$item = $this->db->raw('SELECT ci.cart_item_id FROM cart_items ci
			INNER JOIN carts c ON c.cart_id = ci.cart_id
			WHERE c.user_id = ? AND ci.cart_item_id = ? LIMIT 1', array((int) $user_id, (int) $item_id))->fetch();
		if ( ! $item) { return false; }
		$this->db->table('cart_items')->where(array('cart_item_id' => (int) $item_id))->delete();
		return true;
	}
}
