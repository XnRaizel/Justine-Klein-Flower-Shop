<?php $this->call->view("layouts/admin_header", array("title" => $title)); ?>
<h2>Inventory</h2><table><tr><th>Product</th><th>Stock</th><th>Low-stock level</th><th>Status</th></tr>
<?php foreach ($products as $p): if ($p['is_archived']) continue; ?><tr><td><?= e($p['name']) ?></td><td><?= (int) $p['stock_qty'] ?></td><td><?= (int) $p['low_stock_level'] ?></td><td><span class="badge"><?= $p['stock_qty'] <= $p['low_stock_level'] ? 'Low stock' : 'OK' ?></span></td></tr><?php endforeach; ?></table>
<?php $this->call->view("layouts/admin_footer"); ?>
