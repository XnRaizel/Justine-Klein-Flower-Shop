<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User_model extends Model {
	protected $table = 'users';
	protected $primary_key = 'user_id';

	/** Staff and admin accounts with their role name. */
	public function list_staff() {
		return $this->db->raw("SELECT u.user_id, u.full_name, u.email, u.phone, r.role_name, u.created_at
			FROM users u INNER JOIN roles r ON r.role_id = u.role_id
			WHERE r.role_name IN ('staff', 'admin') ORDER BY r.role_id DESC, u.user_id ASC")->fetchAll();
	}
}
