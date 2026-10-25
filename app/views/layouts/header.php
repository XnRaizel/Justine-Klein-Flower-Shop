<?php $isFlowerSidebarLayout = ($layout ?? '') === 'flower-sidebar'; ?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Home') ?> | Justine Klein Flower Shop</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/redesign.css') ?>">
<script defer src="https://unpkg.com/vue@3.5.13/dist/vue.global.prod.js"></script>
<script defer src="<?= base_url('assets/js/app.js') ?>"></script></head><body class="<?= $isFlowerSidebarLayout ? 'flower-sidebar-page' : '' ?>">
<div id="site-app" class="<?= $isFlowerSidebarLayout ? 'flower-sidebar-layout' : '' ?>" @submit="handleSubmit">
<?php if ($isFlowerSidebarLayout): ?>
<aside class="flower-sidebar" aria-label="Shop navigation">
	<a class="brand" href="<?= site_url('/') ?>">
		<span class="brand-mark" aria-hidden="true">🌸</span>
		<span class="brand-name">Justine Klein<small>FLOWER SHOP</small></span>
	</a>
	<div class="flower-sidebar-group">
		<span class="flower-sidebar-label">Discover</span>
		<nav class="flower-sidebar-nav" aria-label="Discover">
			<a href="<?= site_url('/') ?>"><span aria-hidden="true">⌂</span>Home</a>
			<a href="<?= site_url('shop') ?>"><span aria-hidden="true">✿</span>Shop flowers</a>
			<a href="<?= site_url('custom-orders') ?>"><span aria-hidden="true">☏</span>Custom orders</a>
			<a href="<?= site_url('track') ?>"><span aria-hidden="true">◇</span>Track an order</a>
			<a href="<?= site_url('about') ?>"><span aria-hidden="true">♡</span>Our story</a>
		</nav>
	</div>
	<div class="flower-sidebar-group flower-sidebar-account">
		<span class="flower-sidebar-label">Your account</span>
		<nav class="flower-sidebar-nav" aria-label="Your account">
			<a href="<?= site_url('cart') ?>"><span aria-hidden="true">♧</span>Your cart</a>
			<a href="<?= site_url('login') ?>"><span aria-hidden="true">♙</span>Sign in</a>
		</nav>
		<a class="flower-sidebar-cta" href="<?= site_url('register') ?>">Create an account <span aria-hidden="true">→</span></a>
	</div>
	<div class="flower-sidebar-promo">
		<span aria-hidden="true">✿</span>
		<p>Fresh flowers, thoughtfully arranged for life's lovely moments.</p>
		<a href="<?= site_url('shop') ?>">Explore the collection <span aria-hidden="true">→</span></a>
	</div>
	<p class="flower-sidebar-note"><span aria-hidden="true">●</span> Made with care, every day</p>
</aside>
<div class="flower-sidebar-main">
	<div class="flower-sidebar-topbar"><span>Flowers for life's lovely moments</span></div>
<?php else: ?>
<header class="site-header"><div class="container">
	<a class="brand" href="<?= site_url('/') ?>"><span class="brand-mark" aria-hidden="true">🌸</span><span class="brand-name">Justine Klein<small>FLOWER SHOP</small></span></a>
	<button class="menu-toggle" type="button" v-cloak @click="menuOpen = !menuOpen" :aria-expanded="menuOpen.toString()" aria-controls="site-navigation">Menu</button>
	<nav id="site-navigation" class="nav" :class="{ 'is-open': menuOpen }">
		<a href="<?= site_url('/') ?>" class="<?php active('') ?>">Home</a>
		<a href="<?= site_url('shop') ?>" class="<?php active('shop') ?>">Shop</a>
		<a href="<?= site_url('track') ?>" class="<?php active('track') ?>">Track Order</a>
		<a href="<?= site_url('about') ?>" class="<?php active('about') ?>">About</a>
	</nav>
	<div class="nav-right" :class="{ 'is-open': menuOpen }">
		<a href="<?= site_url('cart') ?>">🛒 Cart</a>
		<a href="<?= site_url('account/orders') ?>">My Orders</a>
		<a href="<?= site_url('login') ?>">Login</a>
		<a class="btn" href="<?= site_url('register') ?>">Sign up</a>
	</div>
</div></header>
<?php endif; ?>
<main>
