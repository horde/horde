--TEST--
Horde_Variables::remove() tests
--FILE--
<?php

require dirname(__FILE__) . '/../../../lib/Horde/Array.php';
require dirname(__FILE__) . '/../../../lib/Horde/Variables.php';

$vars = new Horde_Variables(array(
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

print_r($vars->a);
print "\n";
print_r($vars->b);
print "\n";
print_r($vars->c);
print "\n";
print_r($vars->d);

?>
--EXPECT--
b
Array
(
    [0] => 1
    [1] => 2
    [2] => 3
)

Array
(
    [z] => z
    [y] => Array
        (
            [f] => f
        )

)
