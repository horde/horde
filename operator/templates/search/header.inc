Call Statistics Summary:<br />
<ul>
<li>Total Calls: <?php echo $data['numcalls']; ?></li>
<li>Total Minutes: <?php echo round($data['minutes'], 1); ?></li>
<li>Failed Calls: <?php echo $data['failed']; ?></li>
</ul>
<br />
<table class="striped">
<tr>
<?php
foreach ($columns as $column) {
    echo '<th class="control">' . Operator::getColumnName($column) . '</th>';
}
?>
</tr>
