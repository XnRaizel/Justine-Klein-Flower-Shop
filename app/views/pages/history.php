<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2>My Orders</h2><p class="muted">Showing orders of the demo customer until login is added.</p>
<?php if (empty($orders)): ?><p>No orders yet.</p><?php else: ?>
<table><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr>
<?php foreach ($orders as $o): ?><tr><td><?= e($o['order_number']) ?></td><td><?= e(substr($o['created_at'], 0, 10)) ?></td><td><?= peso($o['total']) ?></td><td><span class="badge"><?= e(status_label($o['status'])) ?></span></td><td><a href="<?= site_url('track?order=' . urlencode($o['order_number'])) ?>">Track</a></td></tr><?php endforeach; ?></table><?php endif; ?></div></section>
<?php $this->call->view("layouts/footer"); ?>
