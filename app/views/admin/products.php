<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<div class="toolbar">
	<h2>Products</h2>
	<a class="btn" href="<?= site_url('admin/products/create') ?>">+ Add product</a>
</div>
<form class="search" method="get" action="<?= site_url('admin/products') ?>">
	<input type="text" name="q" value="<?= e($q) ?>" placeholder="Search by name or occasion">
	<button class="btn small" type="submit">Search</button>
	<?php if ($q !== ''): ?><a class="btn small outline" href="<?= site_url('admin/products') ?>">Clear</a><?php endif; ?>
</form>
<table>
	<tr><th>ID</th><th>Name</th><th>Occasion</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
	<?php if (empty($products)): ?>
		<tr><td colspan="7" class="muted">
			<?= $q !== '' ? 'No products match "' . e($q) . '".' : 'No products yet. Click "Add product" to create one.' ?>
		</td></tr>
	<?php endif; ?>
	<?php foreach ($products as $p): ?>
	<tr>
		<td><?= (int) $p['product_id'] ?></td>
		<td><a href="<?= site_url('admin/products/' . (int) $p['product_id']) ?>"><?= e($p['name']) ?></a></td>
		<td><?= e($p['category_name']) ?></td>
		<td><?= peso($p['price']) ?></td>
		<td><?= (int) $p['stock_qty'] ?></td>
		<td><span class="badge"><?= $p['is_archived'] ? 'Archived' : 'Active' ?></span></td>
		<td class="actions">
			<a class="btn small outline" href="<?= site_url('admin/products/' . (int) $p['product_id']) ?>">View</a>
			<a class="btn small outline" href="<?= site_url('admin/products/' . (int) $p['product_id'] . '/edit') ?>">Edit</a>
			<form method="post" action="<?= site_url('admin/products/' . (int) $p['product_id'] . '/archive') ?>" class="inline">
				<?= csrf_field() ?>
				<button class="btn small outline" type="submit"><?= $p['is_archived'] ? 'Restore' : 'Archive' ?></button>
			</form>
			<a class="btn small danger" href="<?= site_url('admin/products/' . (int) $p['product_id'] . '/delete') ?>">Delete</a>
		</td>
	</tr>
	<?php endforeach; ?>
</table>
<?php $this->call->view("layouts/admin_footer"); ?>
