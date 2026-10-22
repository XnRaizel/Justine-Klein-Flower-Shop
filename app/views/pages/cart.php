<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2>Shopping Cart</h2>
<?php if (empty($items)): ?><p>Your cart is empty. <a href="<?= site_url('shop') ?>">Start shopping</a></p><?php else: ?>
<table><tr><th>Item</th><th>Options</th><th>Qty</th><th>Price</th><th>Total</th></tr>
<?php foreach ($items as $it): ?><tr><td><a href="<?= site_url('product/' . $it['product_id']) ?>"><?= e($it['name']) ?></a></td><td class="muted"><?= e($it['customization']) ?></td><td><?= (int) $it['quantity'] ?></td><td><?= peso($it['price']) ?></td><td><?= peso($it['line_total']) ?></td></tr><?php endforeach; ?>
<tr><th colspan="4" style="text-align:right">Subtotal</th><th><?= peso($subtotal) ?></th></tr></table>
<p style="text-align:right"><a class="btn outline" href="<?= site_url('shop') ?>">Continue shopping</a> <a class="btn" href="<?= site_url('checkout') ?>">Proceed to checkout</a></p><?php endif; ?></div></section>
<?php $this->call->view("layouts/footer"); ?>
