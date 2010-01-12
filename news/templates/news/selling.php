<br />
<br />
<?php
// schedul
if ($row['selling']) {
    $item = explode('|', $row['selling']);
    try {
        echo $registry->call($item[0] . '/getSellingForm', $item[1]);
    } catch (Horde_Exception $e) {
        echo $e->getMessage();
    }
}

