<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Delivery_model extends Model {
	protected $table = 'deliveries';
	protected $primary_key = 'delivery_id';

	public function list_all() {
		return $this->db->raw('SELECT d.*, o.order_number, r.full_name AS rider
			FROM deliveries d
			INNER JOIN orders o ON o.order_id = d.order_id
			LEFT JOIN users r ON r.user_id = d.assigned_to
			ORDER BY d.scheduled_at ASC')->fetchAll();
	}
}
