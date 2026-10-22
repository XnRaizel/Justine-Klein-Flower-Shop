<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Product_model extends Model {
	protected $table = 'products';
	protected $primary_key = 'product_id';

	private $base = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
		FROM products p INNER JOIN categories c ON c.category_id = p.category_id';

	/** Products shown to customers (not archived), optionally one occasion. */
	public function list_active($category_slug = null, $limit = null) {
		$sql = $this->base . ' WHERE p.is_archived = 0';
		$args = array();
		if ($category_slug) { $sql .= ' AND c.slug = ?'; $args[] = $category_slug; }
		$sql .= ' ORDER BY p.product_id ASC';
		if ($limit) { $sql .= ' LIMIT ' . (int) $limit; }
		return $this->db->raw($sql, $args)->fetchAll();
	}

	public function get_product($id) {
		return $this->db->raw($this->base . ' WHERE p.product_id = ? AND p.is_archived = 0', array($id))->fetch();
	}

	/** Every product including archived (admin). Optional search by name or occasion. */
	public function list_admin($search = '') {
		$sql = $this->base;
		$args = array();
		if ($search !== '') {
			$sql .= ' WHERE p.name LIKE ? OR c.name LIKE ?';
			$like = '%' . addcslashes($search, '%_\\') . '%';
			$args = array($like, $like);
		}
		return $this->db->raw($sql . ' ORDER BY p.product_id ASC', $args)->fetchAll();
	}

	/** Active products at or below their low-stock level. */
	public function list_low_stock() {
		return $this->db->raw($this->base . ' WHERE p.is_archived = 0 AND p.stock_qty <= p.low_stock_level ORDER BY p.stock_qty ASC')->fetchAll();
	}

	public function count_low_stock() {
		return count($this->list_low_stock());
	}

	/* ---------------- Checking 3: CRUD ---------------- */

	/** READ one product (admin sees archived ones too). */
	public function find_admin($id) {
		return $this->db->raw($this->base . ' WHERE p.product_id = ?', array((int) $id))->fetch();
	}

	/**
	 * Server-side validation. Returns array(clean_values, errors).
	 * $ignore_id is the product being edited (so its own name is not a duplicate).
	 */
	public function validate(array $in, $ignore_id = null) {
		$errors = array();
		$clean = array(
			'category_id'     => isset($in['category_id']) ? trim((string) $in['category_id']) : '',
			'name'            => isset($in['name']) ? trim((string) $in['name']) : '',
			'description'     => isset($in['description']) ? trim((string) $in['description']) : '',
			'price'           => isset($in['price']) ? trim((string) $in['price']) : '',
			'stock_qty'       => isset($in['stock_qty']) ? trim((string) $in['stock_qty']) : '',
			'low_stock_level' => isset($in['low_stock_level']) ? trim((string) $in['low_stock_level']) : '',
		);

		// Occasion (category): required and must exist
		if ($clean['category_id'] === '') {
			$errors['category_id'] = 'Please choose an occasion.';
		} elseif ( ! ctype_digit($clean['category_id']) ||
			! $this->db->raw('SELECT category_id FROM categories WHERE category_id = ?', array((int) $clean['category_id']))->fetch()) {
			$errors['category_id'] = 'That occasion does not exist.';
		}

		// Name: required, 2-120 characters, unique
		$len = function_exists('mb_strlen') ? mb_strlen($clean['name'], 'UTF-8') : strlen($clean['name']);
		if ($clean['name'] === '') {
			$errors['name'] = 'Product name is required.';
		} elseif ($len < 2 || $len > 120) {
			$errors['name'] = 'Product name must be 2 to 120 characters.';
		} else {
			$dup = $this->db->raw('SELECT product_id FROM products WHERE LOWER(name) = LOWER(?) AND product_id <> ?',
				array($clean['name'], (int) $ignore_id))->fetch();
			if ($dup) { $errors['name'] = 'A product with this name already exists.'; }
		}

		// Description: optional, max 1000 characters
		$dlen = function_exists('mb_strlen') ? mb_strlen($clean['description'], 'UTF-8') : strlen($clean['description']);
		if ($dlen > 1000) { $errors['description'] = 'Description must be 1000 characters or less.'; }

		// Price: required, number greater than 0, up to 2 decimals
		if ($clean['price'] === '') {
			$errors['price'] = 'Price is required.';
		} elseif ( ! preg_match('/^\d{1,6}(\.\d{1,2})?$/', $clean['price'])) {
			$errors['price'] = 'Price must be a number such as 850 or 850.50.';
		} elseif ((float) $clean['price'] <= 0) {
			$errors['price'] = 'Price must be greater than 0.';
		}

		// Stock and low-stock level: whole numbers, 0 or more
		if ($clean['stock_qty'] === '') {
			$errors['stock_qty'] = 'Stock quantity is required.';
		} elseif ( ! preg_match('/^\d{1,6}$/', $clean['stock_qty'])) {
			$errors['stock_qty'] = 'Stock must be a whole number, 0 or more.';
		}
		if ($clean['low_stock_level'] === '') {
			$errors['low_stock_level'] = 'Low-stock level is required.';
		} elseif ( ! preg_match('/^\d{1,6}$/', $clean['low_stock_level'])) {
			$errors['low_stock_level'] = 'Low-stock level must be a whole number, 0 or more.';
		}

		return array($clean, $errors);
	}

	/** Turn validated text values into typed database values. */
	private function typed(array $c) {
		return array(
			'category_id'     => (int) $c['category_id'],
			'name'            => $c['name'],
			'description'     => $c['description'],
			'price'           => number_format((float) $c['price'], 2, '.', ''),
			'stock_qty'       => (int) $c['stock_qty'],
			'low_stock_level' => (int) $c['low_stock_level'],
		);
	}

	/** CREATE. Returns the new product_id. */
	public function create_product(array $clean) {
		return $this->db->table('products')->insert($this->typed($clean));
	}

	/** UPDATE the one row with this id. */
	public function update_product($id, array $clean) {
		return $this->db->table('products')->where(array('product_id' => (int) $id))->update($this->typed($clean));
	}

	/** Archive (hide from customers) or restore. */
	public function set_archived($id, $flag) {
		return $this->db->table('products')->where(array('product_id' => (int) $id))->update(array('is_archived' => $flag ? 1 : 0));
	}

	/** How many records point to this product (decides if it can be deleted). */
	public function usage($id) {
		$id = (int) $id;
		$one = function ($sql) use ($id) {
			$row = $this->db->raw($sql, array($id))->fetch();
			return $row ? (int) $row['n'] : 0;
		};
		return array(
			'orders' => $one('SELECT COUNT(*) AS n FROM order_items WHERE product_id = ?'),
			'stock'  => $one('SELECT COUNT(*) AS n FROM stock_logs WHERE product_id = ?'),
			'carts'  => $one('SELECT COUNT(*) AS n FROM cart_items WHERE product_id = ?'),
		);
	}

	/** DELETE only when no order or stock history depends on the product. */
	public function can_delete($id) {
		$u = $this->usage($id);
		return ($u['orders'] === 0 && $u['stock'] === 0);
	}

	public function delete_product($id) {
		$id = (int) $id;
		$this->db->table('cart_items')->where(array('product_id' => $id))->delete();
		return $this->db->table('products')->where(array('product_id' => $id))->delete();
	}
}
