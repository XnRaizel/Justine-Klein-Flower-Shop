<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Categories = occasions (Birthday, Anniversary, ...)
 */
class Category_model extends Model {
	protected $table = 'categories';
	protected $primary_key = 'category_id';

	public function list_all() {
		return $this->db->table('categories')->order_by('category_id', 'ASC')->get_all();
	}

	public function find_by_slug($slug) {
		return $this->db->table('categories')->where('slug', $slug)->get();
	}
}
