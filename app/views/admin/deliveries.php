<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Deliveries</h2><table><tr><th>Order #</th><th>Recipient</th><th>Address</th><th>Scheduled</th><th>Status</th><th>Rider</th></tr>
<?php foreach ($deliveries as $d): ?><tr><td><?= e($d['order_number']) ?></td><td><?= e($d['recipient_name']) ?></td><td><?= e($d['address']) ?></td><td><?= e(substr($d['scheduled_at'], 0, 16)) ?></td><td><span class="badge"><?= e(status_label($d['status'])) ?></span></td><td><?= e($d['rider'] ?: '—') ?></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
