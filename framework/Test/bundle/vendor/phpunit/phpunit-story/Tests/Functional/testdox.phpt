--TEST--
phpunit --testdox BowlingGameSpec ../_files/BowlingGameSpec.php
--FILE--
<?php
$_SERVER['argv'][1] = '--no-configuration';
$_SERVER['argv'][2] = '--testdox';
$_SERVER['argv'][3] = 'BowlingGameSpec';
$_SERVER['argv'][4] = dirname(dirname(__FILE__)) . '/_files/BowlingGameSpec.php';

require_once 'PHPUnit/Autoload.php';
PHPUnit_TextUI_Command::main();
?>
--EXPECTF--
PHPUnit %s by Sebastian Bergmann.

BowlingGameSpec
 [x] Score for gutter game is 0
 [x] Score for all ones is 20
 [x] Score for one spare and 3 is 16
 [x] Score for one strike and 3 and 4 is 24
 [x] Score for perfect game is 300
