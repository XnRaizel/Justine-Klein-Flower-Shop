<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?= e($title ?? 'Staff & Admin Login') ?> | Bloom Express</title>
	<link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
	<link rel="stylesheet" href="<?= base_url('assets/css/redesign.css') ?>">
	<script defer src="https://unpkg.com/vue@3.5.13/dist/vue.global.prod.js"></script>
	<script defer src="<?= base_url('assets/js/app.js') ?>"></script>
</head>
<body>
	<div id="admin-app" @submit="handleSubmit" style="min-height:100vh;display:grid;place-items:center;padding:24px;background:#211b24">
	<main class="card" style="width:100%;max-width:420px">
		<h1 style="margin-top:0;text-align:center">Staff &amp; Admin Login</h1>
		<div class="form">
			<label for="email">Email</label>
			<input id="email" type="email" name="email" autocomplete="username">
			<label for="password">Password</label>
			<input id="password" type="password" name="password" autocomplete="current-password">
			<a class="btn" href="<?= site_url('admin') ?>" style="display:block;margin-top:20px;text-align:center">Login</a>
		</div>
		<p style="margin-bottom:0;text-align:center"><a href="<?= site_url('/') ?>">Back to shop</a></p>
	</main>
	</div>
</body>
</html>
