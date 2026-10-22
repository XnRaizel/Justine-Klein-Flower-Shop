<?php
$this->call->view("layouts/admin_header", array("title" => $title));
$is_edit = ($mode === 'edit');
$action  = $is_edit ? site_url('admin/products/' . (int) $product['product_id'] . '/update') : site_url('admin/products/store');
?>
<p><a href="<?= site_url('admin/products') ?>">← Back to products</a></p>
<h2><?= $is_edit ? 'Edit product #' . (int) $product['product_id'] : 'Add product' ?></h2>
<?php if (isset($errors['_form'])): ?><div class="alert error"><?= e($errors['_form']) ?></div><?php endif; ?>
<?php if ($errors && ! isset($errors['_form'])): ?><div class="alert error">Please fix the highlighted fields and try again.</div><?php endif; ?>
<form class="form wide" method="post" action="<?= $action ?>" novalidate>
	<?= csrf_field() ?>

	<label for="category_id">Occasion *</label>
	<select id="category_id" name="category_id" class="<?= isset($errors['category_id']) ? 'invalid' : '' ?>">
		<option value="">-- Choose an occasion --</option>
		<?php foreach ($categories as $c): ?>
			<option value="<?= (int) $c['category_id'] ?>" <?= (string) $values['category_id'] === (string) $c['category_id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
		<?php endforeach; ?>
	</select>
	<?= field_error($errors, 'category_id') ?>

	<label for="name">Product name *</label>
	<input id="name" type="text" name="name" maxlength="120" value="<?= e($values['name']) ?>" class="<?= isset($errors['name']) ? 'invalid' : '' ?>">
	<?= field_error($errors, 'name') ?>

	<label for="description">Description</label>
	<textarea id="description" name="description" rows="4" class="<?= isset($errors['description']) ? 'invalid' : '' ?>"><?= e($values['description']) ?></textarea>
	<?= field_error($errors, 'description') ?>

	<label for="price">Price (₱) *</label>
	<input id="price" type="text" inputmode="decimal" name="price" value="<?= e($values['price']) ?>" placeholder="e.g. 850.00" class="<?= isset($errors['price']) ? 'invalid' : '' ?>">
	<?= field_error($errors, 'price') ?>

	<label for="stock_qty">Stock quantity *</label>
	<input id="stock_qty" type="text" inputmode="numeric" name="stock_qty" value="<?= e($values['stock_qty']) ?>" class="<?= isset($errors['stock_qty']) ? 'invalid' : '' ?>">
	<?= field_error($errors, 'stock_qty') ?>

	<label for="low_stock_level">Low-stock level *</label>
	<input id="low_stock_level" type="text" inputmode="numeric" name="low_stock_level" value="<?= e($values['low_stock_level']) ?>" class="<?= isset($errors['low_stock_level']) ? 'invalid' : '' ?>">
	<?= field_error($errors, 'low_stock_level') ?>

	<p class="actions" style="margin-top:18px">
		<button class="btn" type="submit"><?= $is_edit ? 'Save changes' : 'Add product' ?></button>
		<a class="btn outline" href="<?= site_url('admin/products') ?>">Cancel</a>
	</p>
</form>
<?php $this->call->view("layouts/admin_footer"); ?>
