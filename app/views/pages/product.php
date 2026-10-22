<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><p><a href="<?= site_url('shop') ?>">← Back to shop</a></p>
<div class="card" style="display:grid;grid-template-columns:1fr 1fr;gap:24px"><div class="img" style="height:260px;font-size:6rem"><?= occasion_emoji($product['category_slug']) ?></div>
<div><h2><?= e($product['name']) ?></h2><span class="badge"><?= e($product['category_name']) ?></span>
<p><?= e($product['description']) ?></p><p class="price" style="font-size:1.4rem"><?= peso($product['price']) ?></p>
<p class="muted"><?= $product['stock_qty'] > 0 ? (int) $product['stock_qty'] . ' in stock' : 'Out of stock' ?></p>
<?php if (!empty($addons)): ?><h4>Customize (add-ons)</h4>
<?php foreach ($addons as $type => $list): ?><p class="muted" style="margin:6px 0 2px"><b><?= e(ucfirst($type)) ?></b></p>
<?php foreach ($list as $a): ?><div class="muted">• <?= e($a['name']) ?><?= $a['price'] > 0 ? ' (+' . peso($a['price']) . ')' : '' ?></div><?php endforeach; endforeach; endif; ?>
<p><a class="btn" href="<?= site_url('cart') ?>">Add to cart</a></p></div></div></div></section>
<?php $this->call->view("layouts/footer"); ?>
