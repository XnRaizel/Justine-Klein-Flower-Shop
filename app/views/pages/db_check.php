<?php $this->call->view("layouts/header", array("title" => $title)); ?>
<section><div class="container"><h2>Database Check</h2>
<div class="card" style="border-color:#2e7d5b"><b style="color:#2e7d5b">✔ Connected</b><p class="muted" style="margin-bottom:0">Driver: <?= e($driver) ?> · Host: <?= e($host) ?> · Database: <?= e($dbname) ?></p></div><br>
<table><tr><th>Table</th><th>Records</th></tr><?php foreach ($counts as $t => $n): ?><tr><td><?= e($t) ?></td><td><?= (int) $n ?></td></tr><?php endforeach; ?></table></div></section>
<?php $this->call->view("layouts/footer"); ?>
