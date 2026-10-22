<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2>Checkout</h2>
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:24px">
<form class="form" style="max-width:none;width:100%" onsubmit="return false">
<label>Recipient name</label><input type="text"><label>Delivery address</label><textarea rows="2"></textarea>
<label>Delivery date &amp; time</label><input type="datetime-local"><label>Card message</label><textarea rows="2"></textarea>
<p class="muted">Map pinning, delivery fee and PayMongo payment are added in later phases.</p><button class="btn">Place order</button></form>
<div><h4>Order summary</h4><table><?php foreach ($items as $it): ?><tr><td><?= e($it['name']) ?> × <?= (int) $it['quantity'] ?></td><td><?= peso($it['line_total']) ?></td></tr><?php endforeach; ?>
<tr><th>Subtotal</th><th><?= peso($subtotal) ?></th></tr></table></div></div></div></section>
<?php $this->call->view("layouts/footer"); ?>
