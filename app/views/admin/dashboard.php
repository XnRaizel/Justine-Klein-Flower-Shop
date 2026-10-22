<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Dashboard</h2><div class="stats"><div class="card stat"><b><?= (int) $pending ?></b>Pending orders</div><div class="card stat"><b><?= (int) $delivering ?></b>Out for delivery</div><div class="card stat"><b><?= (int) $low_stock ?></b>Low-stock items</div><div class="card stat"><b><?= peso($week_sales) ?></b>Sales (last 7 days)</div></div>
<h3>Recent orders</h3><table><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th></tr>
<?php foreach ($recent as $o): ?><tr><td><?= e($o['order_number']) ?></td><td><?= e($o['customer']) ?></td><td><?= peso($o['total']) ?></td><td><span class="badge"><?= e(status_label($o['status'])) ?></span></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
