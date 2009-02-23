--TEST--
Horde_Text_Flowed:: tests
--FILE--
<?php

require_once 'Horde/Util.php';
require_once 'Horde/String.php';
require_once 'Horde/Text/Flowed.php';

echo "[FIXED -> FLOWED]\n";

$flowed = new Horde_Text_Flowed("Hello, world!");
echo $flowed->toFlowed() . "\n";

$flowed = new Horde_Text_Flowed("Hello, \nworld!");
echo $flowed->toFlowed() . "\n";

$flowed = new Horde_Text_Flowed("Hello, \n world!");
echo $flowed->toFlowed() . "\n";

$flowed = new Horde_Text_Flowed("From");
echo $flowed->toFlowed() . "\n";

// See Bug #2969
$flowed = new Horde_Text_Flowed("   >--------------------------------------------------------------------------------------------------------------------------------");
echo $flowed->toFlowed() . "\n";

echo "[FLOWED -> FIXED]\n";

$flowed = new Horde_Text_Flowed(">line 1 \n>line 2 \n>line 3");
echo $flowed->toFixed() . "\n\n";
$flowed = new Horde_Text_Flowed(">line 1 \n>line 2 \n>line 3");
echo $flowed->toFixed() . "\n\n";

// See Bug #4832
$flowed = new Horde_Text_Flowed("line 1\n>from line 2\nline 3");
echo $flowed->toFixed() . "\n\n";
$flowed = new Horde_Text_Flowed("line 1\n From line 2\nline 3");
echo $flowed->toFixed() . "\n";

?>
--EXPECT--
[FIXED -> FLOWED]
Hello, world!

Hello,
world!

Hello,
  world!

 From

    
>-------------------------------------------------------------------------------------------------------------------------------- 

[FLOWED -> FIXED]
>line 1 line 2 line 3

>line 1 line 2 line 3

line 1
>from line 2
line 3

line 1
From line 2
line 3
