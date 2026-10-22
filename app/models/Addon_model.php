<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Addon_model extends Model {
	protected $table = 'addons';
	protected $primary_key = 'addon_id';

	/** Add-ons grouped by type: flower, color, extra. */
	public function list_grouped() {
		$rows = $this->db->table('addons')->order_by('addon_id', 'ASC')->get_all();
		$grouped = array();
		foreach ($rows as $r) { $grouped[$r['type']][] = $r; }
		return $grouped;
	}
}
