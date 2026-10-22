<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Staff &amp; Roles</h2><table><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th></tr>
<?php foreach ($staff as $s): ?><tr><td><?= e($s['full_name']) ?></td><td><?= e($s['email']) ?></td><td><?= e($s['phone']) ?></td><td><span class="badge"><?= e(ucfirst($s['role_name'])) ?></span></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
