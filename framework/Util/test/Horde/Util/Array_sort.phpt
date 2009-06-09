--TEST--
Horde_Array::arraySort() tests
--FILE--
<?php

require dirname(__FILE__) . '/../Array.php';
$array = array(
    array('name' => 'foo', 'desc' => 'foo long desc'),
    array('name' => 'aaaa', 'desc' => 'aaa foo long desc'),
    array('name' => 'baby', 'desc' => 'The test data was boring'),
    array('name' => 'zebra', 'desc' => 'Striped armadillos'),
    array('name' => 'umbrage', 'desc' => 'resentment'),
);

Horde_Array::arraySort($array);
print_r($array);

Horde_Array::arraySort($array, 'desc');
print_r($array);

?>
--EXPECT--
Array
(
    [1] => Array
        (
            [name] => aaaa
            [desc] => aaa foo long desc
        )

    [2] => Array
        (
            [name] => baby
            [desc] => The test data was boring
        )

    [0] => Array
        (
            [name] => foo
            [desc] => foo long desc
        )

    [4] => Array
        (
            [name] => umbrage
            [desc] => resentment
        )

    [3] => Array
        (
            [name] => zebra
            [desc] => Striped armadillos
        )

)
Array
(
    [1] => Array
        (
            [name] => aaaa
            [desc] => aaa foo long desc
        )

    [0] => Array
        (
            [name] => foo
            [desc] => foo long desc
        )

    [4] => Array
        (
            [name] => umbrage
            [desc] => resentment
        )

    [3] => Array
        (
            [name] => zebra
            [desc] => Striped armadillos
        )

    [2] => Array
        (
            [name] => baby
            [desc] => The test data was boring
        )

)
