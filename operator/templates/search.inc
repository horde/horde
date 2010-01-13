<div>
Call Statistics Summary:<br />
<ul>
<li>Total Calls: <?php echo $stats['numcalls']; ?></li>
<li>Total Minutes: <?php echo round($stats['minutes'], 1); ?></li>
<li>Failed Calls: <?php echo $stats['failed']; ?></li>
</ul>
<br />
<?php echo $pager->render(); ?>
<table id="callDetails" class="striped">
<tr>
<?php
foreach ($columns as $column) {
    echo '<th class="control">' . Operator::getColumnName($column) . '</th>';
}
?>
</tr>

<?php
foreach ($data as $record) {
    echo "<tr>\n";
    foreach ($columns as $column) {
        echo "<td>";
        if ($column == 'amaflags') {
            $record[$column] = Operator::getAMAFlagName($record[$column]);
        }
        echo $record[$column];
        echo "</td>\n";
    }
    echo "</tr>\n";
}
?>

</table>
<?php echo $pager->render(); ?>
</div>
