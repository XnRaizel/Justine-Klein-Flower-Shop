<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<p><a href="<?= site_url('admin/products') ?>">← Back to products</a></p>
<h2>Delete product</h2>
<div class="card" style="max-width:560px">
	<p>You are about to delete:</p>
	<p><b><?= e($product['name']) ?></b> (ID <?= (int) $product['product_id'] ?>, <?= e($product['category_name']) ?>, <?= peso($product['price']) ?>)</p>
	<?php if ($can_delete): ?>
		<div class="alert error">This permanently removes the product from the database. It cannot be undone.</div>
		<form method="post" action="<?= site_url('admin/products/' . (int) $product['product_id'] . '/delete') ?>" class="actions">
			<?= csrf_field() ?>
			<button class="btn danger" type="submit">Yes, delete permanently</button>
			<a class="btn outline" href="<?= site_url('admin/products') ?>">Cancel</a>
		</form>
	<?php else: ?>
		<div class="alert error">
			This product cannot be deleted because it has history
			(<?= (int) $usage['orders'] ?> order line(s), <?= (int) $usage['stock'] ?> stock log(s)).
			Deleting it would break past orders. Archive it instead to hide it from customers.
		</div>
		<form method="post" action="<?= site_url('admin/products/' . (int) $product['product_id'] . '/archive') ?>" class="actions">
			<?= csrf_field() ?>
			<?php if ( ! $product['is_archived']): ?><button class="btn" type="submit">Archive instead</button><?php endif; ?>
			<a class="btn outline" href="<?= site_url('admin/products') ?>">Back</a>
		</form>
	<?php endif; ?>
</div>
<?php $this->call->view("layouts/admin_footer"); ?>
