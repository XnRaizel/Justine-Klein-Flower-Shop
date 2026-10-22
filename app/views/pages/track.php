<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2 style="text-align:center">Track your order</h2>
<form class="form" method="get" action="<?= site_url('track') ?>"><label>Order number</label><input type="text" name="order" placeholder="e.g. BE-0005" value="<?= e($number) ?>"><br><br><button class="btn">Track</button>
<p class="muted">Try BE-0001 to BE-0009.</p></form>
<?php if ($number !== '' && !$order): ?><p style="text-align:center;color:#c2185b">No order found for “<?= e($number) ?>”.</p><?php endif; ?>
<?php if ($order): $steps = array('pending','confirmed','preparing','out_for_delivery','delivered'); $cur = array_search($order['status'], $steps); ?>
<div class="card" style="margin-top:20px"><h3 style="margin-top:0">Order <?= e($order['order_number']) ?> <span class="badge"><?= e(status_label($order['status'])) ?></span></h3>
<?php if ($order['status'] === 'cancelled'): ?><p>This order was cancelled.</p><?php else: ?>
<div style="display:flex;gap:6px;margin:12px 0"><?php foreach ($steps as $i => $s): ?><div class="chip <?= $i <= $cur ? 'active' : '' ?>" style="flex:1;text-align:center"><?= e(status_label($s)) ?></div><?php endforeach; ?></div><?php endif; ?>
<table><tr><th>Item</th><th>Options</th><th>Qty</th><th>Price</th></tr>
<?php foreach ($items as $it): ?><tr><td><?= e($it['product_name']) ?></td><td class="muted"><?= e(implode(', ', array_column($it['addons'], 'name'))) ?></td><td><?= (int) $it['quantity'] ?></td><td><?= peso($it['unit_price']) ?></td></tr><?php endforeach; ?>
<tr><td colspan="3" style="text-align:right">Delivery fee</td><td><?= peso($order['delivery_fee']) ?></td></tr>
<tr><th colspan="3" style="text-align:right">Total</th><th><?= peso($order['total']) ?></th></tr></table>
<?php if ($delivery): ?><p><b>Deliver to:</b> <?= e($delivery['recipient_name']) ?>, <?= e($delivery['address']) ?><br><b>Scheduled:</b> <?= e($delivery['scheduled_at']) ?> · <b>Status:</b> <?= e(status_label($delivery['status'])) ?><?= $delivery['rider'] ? ' · <b>Rider:</b> ' . e($delivery['rider']) : '' ?></p><?php endif; ?>
<?php if ($order['card_message']): ?><p class="muted">Card message: “<?= e($order['card_message']) ?>”</p><?php endif; ?></div><?php endif; ?></div></section>
<?php $this->call->view("layouts/footer"); ?>
