<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Sales Reports</h2><p class="muted">Last 7 days, cancelled orders excluded.</p>
<table><tr><th>Date</th><th>Orders</th><th>Sales</th></tr>
<?php foreach ($days as $d): ?><tr><td><?= e($d['day']) ?></td><td><?= (int) $d['orders'] ?></td><td><?= peso($d['sales']) ?></td></tr><?php endforeach; if (empty($days)): ?><tr><td colspan="3">No sales in this period.</td></tr><?php endif; ?></table>
<h3>Top products</h3><table><tr><th>Product</th><th>Qty sold</th><th>Revenue</th></tr>
<?php foreach ($top as $t): ?><tr><td><?= e($t['name']) ?></td><td><?= (int) $t['qty'] ?></td><td><?= peso($t['revenue']) ?></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
