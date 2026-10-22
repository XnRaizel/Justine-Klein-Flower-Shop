<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<div class="hero"><div class="container hero-inner"><div class="hero-copy">
<span class="eyebrow">A little joy, delivered</span><h1>Fresh flowers,<br>delivered with love.</h1>
<p>Browse bouquets by occasion, customize your arrangement, and schedule delivery.</p>
<div class="hero-actions"><a class="btn" href="<?= site_url('shop') ?>">Explore the collection <span aria-hidden="true">→</span></a><span class="hero-promise">Thoughtfully arranged, always.</span></div>
</div><div class="hero-art" aria-hidden="true"><div class="hero-art-circle"></div><span class="hero-flower flower-one">🌷</span><span class="hero-flower flower-two">🌸</span><span class="hero-flower flower-three">🌼</span><span class="hero-art-label">Made to make<br>their day</span></div></div></div>
<section class="home-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">Find your moment</span><h2>Flowers for every occasion</h2></div><a class="text-link" href="<?= site_url('shop') ?>">Browse all <span aria-hidden="true">→</span></a></div><div class="grid">
<?php foreach ($categories as $c): ?>
<a class="card category-card" href="<?= site_url('shop/occasion/' . $c['slug']) ?>"><div class="img"><?= occasion_emoji($c['slug']) ?></div><b><?= e($c['name']) ?></b><span class="category-link">Shop occasion <span aria-hidden="true">→</span></span></a>
<?php endforeach; ?></div></div></section>
<section class="featured-section"><div class="container"><div class="section-heading"><div><span class="eyebrow">Picked with love</span><h2>Featured bouquets</h2></div><a class="text-link" href="<?= site_url('shop') ?>">Shop all flowers <span aria-hidden="true">→</span></a></div><div class="grid">
<?php foreach ($featured as $p): ?>
<div class="card featured-card"><div class="img"><?= occasion_emoji($p['category_slug']) ?></div><b><?= e($p['name']) ?></b><div class="price"><?= peso($p['price']) ?></div><a href="<?= site_url('product/' . $p['product_id']) ?>">View details <span aria-hidden="true">→</span></a></div>
<?php endforeach; ?></div></div></section>
<?php $this->call->view("layouts/footer"); ?>
