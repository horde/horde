<br />
<br />
<?php
// schedul
if ($row['selling']) {
    $item = explode('|', $row['selling']);
    $sell_from = $registry->call($item[0] . '/getSellingForm', $item[1]);
    if ($sell_from instanceof PEAR_Error) {
        echo $sell_from->getMessage();
    } else {
        echo $sell_from;
    }
}

