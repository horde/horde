<table class="striped">
<tr>
<?php
foreach ($columns as $column) {
    echo '<th class="control">' . Operator::getColumnName($column) . '</th>';
}
?>
</tr>
