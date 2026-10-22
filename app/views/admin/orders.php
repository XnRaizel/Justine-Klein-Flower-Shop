<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Orders</h2><table><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Delivery</th></tr>
<?php foreach ($orders as $o): ?><tr><td><?= e($o['order_number']) ?></td><td><?= e($o['customer']) ?></td><td><?= e(substr($o['created_at'], 0, 16)) ?></td><td><?= peso($o['total']) ?></td><td><span class="badge"><?= e(status_label($o['status'])) ?></span></td><td><?= e(status_label($o['delivery_status'])) ?></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
