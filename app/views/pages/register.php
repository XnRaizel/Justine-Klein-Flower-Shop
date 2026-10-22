<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2 style="text-align:center">Create account</h2><form class="form" onsubmit="return false"><label>Full name</label><input type="text"><label>Email</label><input type="email"><label>Phone</label><input type="text"><label>Password</label><input type="password"><br><br><button class="btn">Sign up</button>
<p class="muted">Already registered? <a href="<?= site_url('login') ?>">Login</a></p></form></div></section>
<?php $this->call->view("layouts/footer"); ?>
