<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Admin') ?> | Bloom Express Admin</title>
<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/redesign.css') ?>">
<script defer src="https://unpkg.com/vue@3.5.13/dist/vue.global.prod.js"></script>
<script defer src="<?= base_url('assets/js/app.js') ?>"></script></head><body>
<div id="admin-app" class="admin" @submit="handleSubmit" :class="{ 'sidebar-open': sidebarOpen }">
<aside id="admin-navigation" class="sidebar">
	<a class="brand" href="<?= site_url('admin') ?>">🌸 Bloom Admin</a>
	<?php $links = array('' => 'Dashboard', 'products' => 'Products', 'orders' => 'Orders', 'deliveries' => 'Deliveries', 'inventory' => 'Inventory', 'staff' => 'Staff & Roles', 'reports' => 'Sales Reports');
	$seg = trim(substr(strtok($_SERVER['REQUEST_URI'], '?'), strlen('/admin')), '/');
	foreach ($links as $path => $label): ?>
		<a href="<?= site_url('admin' . ($path ? '/' . $path : '')) ?>" class="<?= ($seg === $path || ($path !== '' && strpos($seg, $path . '/') === 0)) ? 'active' : '' ?>"><?= e($label) ?></a>
	<?php endforeach; ?>
	<a href="<?= site_url('/') ?>">← Back to shop</a>
</aside>
<main>
<button class="admin-menu-toggle" type="button" v-cloak @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-controls="admin-navigation">Menu</button>
<?php flash_render(); ?>
