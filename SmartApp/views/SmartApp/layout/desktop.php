<!DOCTYPE html>
<html lang="en"
<?php if (Q_Config::get('Q', 'environment', '') == 'live'): ?>
<?php endif ?>
xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
	<?php include(dirname(__FILE__).'/common_header.php') ?>
</head>
<body>
	<div class="Q_orientation_mask">
		<?php echo Q_Html::img('plugins/Q/img/ui/qbix_logo_small.png') ?> 
	</div>
	<div id="main">
		<div class="Q_top_stub">&nbsp;</div>
		<div id="dashboard_slot">
<!-- ------------------------begin dashboard slot-------------------------------- -->
<?php echo $dashboard ?> 
<!-- --------------------------end dashboard slot-------------------------------- -->
		</div>
		<div id="notices_slot">
<!-- --------------------------begin notices slot-------------------------------- -->
<?php echo $notices ?>
<!-- ----------------------------end notices slot-------------------------------- -->
		</div>
		<div id="column0_slot">
<!-- --------------------------begin column0 slot-------------------------------- -->
<?php echo $column0 ?>
<!-- ----------------------------end column0 slot-------------------------------- -->
		</div>
		<div id="columns_flip">
			<div id="column1_slot">
<!-- --------------------------begin column1 slot-------------------------------- -->
<?php echo $column1 ?>
<!-- ----------------------------end column1 slot-------------------------------- -->
			</div>
			<div id="column2_slot">
<!-- --------------------------begin column2 slot-------------------------------- -->
<?php echo $column2 ?>
<!-- ----------------------------end column2 slot-------------------------------- -->
			</div>
		</div>
		<?php include(dirname(__FILE__).'/common_footer.php') ?> 
	</div>
</body>
</html>
