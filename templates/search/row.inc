<tr>
<?php
foreach ($columns as $column) {
    echo "<td>";
    if ($column == 'amaflags') {
        $record[$column] = Operator::getAMAFlagName($record[$column]);
    }
    echo $record[$column];
    echo "</td>";
}
?>
</tr>
