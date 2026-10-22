<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<p><a href="<?= site_url('admin/products') ?>">← Back to products</a></p>
<div class="toolbar">
	<h2><?= e($product['name']) ?></h2>
	<span class="badge"><?= $product['is_archived'] ? 'Archived' : 'Active' ?></span>
</div>
<table class="detail">
	<tr><th>Product ID</th><td><?= (int) $product['product_id'] ?></td></tr>
	<tr><th>Occasion</th><td><?= e($product['category_name']) ?></td></tr>
	<tr><th>Description</th><td><?= $product['description'] !== '' ? nl2br(e($product['description'])) : '<span class="muted">No description</span>' ?></td></tr>
	<tr><th>Price</th><td><?= peso($product['price']) ?></td></tr>
	<tr><th>Stock</th><td><?= (int) $product['stock_qty'] ?> <?= $product['stock_qty'] <= $product['low_stock_level'] ? '<span class="badge">Low stock</span>' : '' ?></td></tr>
	<tr><th>Low-stock level</th><td><?= (int) $product['low_stock_level'] ?></td></tr>
	<tr><th>Added on</th><td><?= e($product['created_at']) ?></td></tr>
	<tr><th>Order history</th><td><?= (int) $usage['orders'] ?> order line(s), <?= (int) $usage['stock'] ?> stock log(s)</td></tr>
</table>
<p class="actions" style="margin-top:16px">
	<a class="btn" href="<?= site_url('admin/products/' . (int) $product['product_id'] . '/edit') ?>">Edit</a>
	<a class="btn danger" href="<?= site_url('admin/products/' . (int) $product['product_id'] . '/delete') ?>">Delete</a>
</p>
<?php $this->call->view("layouts/admin_footer"); ?>
