<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2 style="text-align:center">Login</h2><form class="form" onsubmit="return false"><label>Email</label><input type="email"><label>Password</label><input type="password"><br><br><button class="btn">Login</button>
<p class="muted">No account? <a href="<?= site_url('register') ?>">Sign up</a></p></form></div></section>
<?php $this->call->view("layouts/footer"); ?>
