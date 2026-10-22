<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<?php
$vue_products = array_map(function ($product) {
	return array(
		'product_id'   => (int) $product['product_id'],
		'name'         => (string) $product['name'],
		'category_name'=> (string) $product['category_name'],
		'emoji'        => occasion_emoji($product['category_slug']),
		'stock_qty'    => (int) $product['stock_qty'],
		'price'        => peso($product['price']),
	);
}, $products);
?>
<section><div class="container"><h2><?= e($heading) ?></h2>
<div class="chips"><a class="chip <?= $current === '' ? 'active' : '' ?>" href="<?= site_url('shop') ?>">All</a>
<?php foreach ($categories as $c): ?><a class="chip <?= $current === $c['slug'] ? 'active' : '' ?>" href="<?= site_url('shop/occasion/' . $c['slug']) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
<div id="shop-vue" v-cloak data-product-url="<?= e(site_url('product/')) ?>">
	<div class="search shop-filters">
		<label class="sr-only" for="shop-search">Search products</label>
		<input id="shop-search" type="search" v-model.trim="query" placeholder="Search bouquets...">
		<label class="stock-filter"><input type="checkbox" v-model="inStockOnly"> In stock only</label>
	</div>
	<p class="muted" aria-live="polite">{{ visibleProducts.length }} product(s)</p>
	<div class="grid">
		<div class="card" v-for="product in visibleProducts" :key="product.product_id">
			<div class="img">{{ product.emoji }}</div>
			<b>{{ product.name }}</b>
			<div class="muted">{{ product.category_name }} · {{ product.stock_qty > 0 ? product.stock_qty + ' in stock' : 'Out of stock' }}</div>
			<div class="price">{{ product.price }}</div>
			<a :href="productUrl + product.product_id">View details →</a>
		</div>
		<p v-if="visibleProducts.length === 0">No products match your filters.</p>
	</div>
</div>
<div id="shop-fallback" class="grid"><?php foreach ($products as $p): ?>
<div class="card"><div class="img"><?= occasion_emoji($p['category_slug']) ?></div><b><?= e($p['name']) ?></b><div class="muted"><?= e($p['category_name']) ?> · <?= $p['stock_qty'] > 0 ? (int) $p['stock_qty'] . ' in stock' : 'Out of stock' ?></div><div class="price"><?= peso($p['price']) ?></div><a href="<?= site_url('product/' . $p['product_id']) ?>">View details →</a></div>
<?php endforeach; if (empty($products)): ?><p>No products found.</p><?php endif; ?></div></div></section>
<script type="application/json" id="shop-products"><?= json_encode($vue_products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_THROW_ON_ERROR) ?></script>
<?php $this->call->view("layouts/footer"); ?>
