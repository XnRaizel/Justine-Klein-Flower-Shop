<?php
$tableCount = count($counts);
$totalRecords = array_sum($counts);
$this->call->view("layouts/header", array("title" => $title, "layout" => "flower-sidebar"));
?>
<section class="db-check-page">
	<div class="container">
		<header class="db-check-banner">
			<div>
				<span class="db-check-eyebrow">Justine Klein Flower Shop · System status</span>
				<h1>Database check</h1>
				<p>A quick look at your shop's connection and stored records.</p>
			</div>
			<div class="db-check-flower" aria-hidden="true">✿</div>
		</header>

		<div class="db-check-connection" role="status">
			<div class="db-check-connection-icon" aria-hidden="true">✓</div>
			<div class="db-check-connection-copy">
				<strong>Connected and ready</strong>
				<p>
					<span>Driver <b><?= e($driver) ?></b></span>
					<span>Host <b><?= e($host) ?></b></span>
					<span>Database <b><?= e($dbname) ?></b></span>
				</p>
			</div>
			<span class="db-check-live"><span></span> Live</span>
		</div>

		<div class="db-check-stats" aria-label="Database summary">
			<div class="db-check-stat">
				<span>Tables monitored</span>
				<strong><?= $tableCount ?></strong>
				<small>Shop data collections</small>
			</div>
			<div class="db-check-stat">
				<span>Total records</span>
				<strong><?= number_format($totalRecords) ?></strong>
				<small>Across monitored tables</small>
			</div>
		</div>

		<div class="db-check-table-card">
			<div class="db-check-table-heading">
				<div>
					<span class="db-check-eyebrow">Your shop data</span>
					<h2>Table overview</h2>
				</div>
				<span><?= $tableCount ?> tables</span>
			</div>
			<div class="db-check-table-wrap">
				<table class="db-check-table">
					<thead>
						<tr><th scope="col">Table</th><th scope="col">Records</th></tr>
					</thead>
					<tbody>
						<?php foreach ($counts as $t => $n): ?>
						<tr>
							<td><span class="db-check-table-icon" aria-hidden="true">✿</span><?= e($t) ?></td>
							<td><span class="db-check-count"><?= number_format((int) $n) ?></span></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>
<?php $this->call->view("layouts/footer", array("layout" => "flower-sidebar")); ?>
