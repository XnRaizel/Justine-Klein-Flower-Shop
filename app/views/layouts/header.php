<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Home') ?> | Bloom Express</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/redesign.css') ?>">
<script defer src="https://unpkg.com/vue@3.5.13/dist/vue.global.prod.js"></script>
<script defer src="<?= base_url('assets/js/app.js') ?>"></script></head><body>
<div id="site-app" @submit="handleSubmit">
<header class="site-header"><div class="container">
	<a class="brand" href="<?= site_url('/') ?>">🌸 Bloom<span>Express</span></a>
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
<main>
