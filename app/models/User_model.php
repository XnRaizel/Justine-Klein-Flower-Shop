<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User_model extends Model {
	protected $table = 'users';
	protected $primary_key = 'user_id';

	/** Staff and admin accounts with their role name. */
	public function list_staff() {
		return $this->db->raw("SELECT u.user_id, u.full_name, u.email, u.phone, r.role_name, u.created_at
			FROM users u INNER JOIN roles r ON r.role_id = u.role_id
			WHERE r.role_name IN ('staff', 'rider', 'admin')
			ORDER BY FIELD(r.role_name, 'admin', 'staff', 'rider'), u.user_id ASC")->fetchAll();
	}

	public function list_riders() {
		return $this->db->raw("SELECT u.user_id, u.full_name
			FROM users u INNER JOIN roles r ON r.role_id = u.role_id
			WHERE r.role_name = 'rider' ORDER BY u.full_name, u.user_id")->fetchAll();
	}

	public function set_delivery_role($user_id, $role_name) {
		if ( ! in_array($role_name, array('staff', 'rider'), true)) { return false; }
		$role = $this->db->raw('SELECT role_id FROM roles WHERE role_name = ? LIMIT 1', array($role_name))->fetch();
		if ( ! $role) { return false; }
		$target = $this->db->raw("SELECT u.user_id, r.role_name FROM users u
			INNER JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1",
			array((int) $user_id))->fetch();
		if ( ! $target || ! in_array($target['role_name'], array('staff', 'rider'), true)) { return false; }
		if ($target['role_name'] === $role_name) { return true; }
		if ($target['role_name'] === 'rider' && $role_name === 'staff') {
			$active_deliveries = $this->db->raw("SELECT delivery_id FROM deliveries
				WHERE assigned_to = ? AND status IN ('scheduled', 'preparing', 'out_for_delivery') LIMIT 1",
				array((int) $user_id))->fetch();
			if ($active_deliveries) { return false; }
		}
		return $this->db->table('users')->where(array('user_id' => (int) $user_id))
			->update(array('role_id' => (int) $role['role_id']));
	}
}
