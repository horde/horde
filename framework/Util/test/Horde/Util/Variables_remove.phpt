--TEST--
Variables::remove() tests
--FILE--
<?php

require dirname(__FILE__) . '/../Variables.php';
$vars = new Variables(array(
   'a' => 'a',
   'b' => 'b',
   'c' => array(1, 2, 3),
   'd' => array(
       'z' => 'z',
       'y' => array(
           'f' => 'f',
           'g' => 'g'
       )
   )
));

$vars->remove('a');
$vars->remove('d[y][g]');
print_r($vars->_vars);

?>
--EXPECT--
Array
(
    [b] => b
    [c] => Array
        (
            [0] => 1
            [1] => 2
            [2] => 3
        )

    [d] => Array
        (
            [z] => z
            [y] => Array
                (
                    [f] => f
                )

        )

)
