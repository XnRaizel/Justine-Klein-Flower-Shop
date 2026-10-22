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
}
