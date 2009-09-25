--TEST--
Horde_Text_Filter_Environment tests
--FILE--
<?php

require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter.php';
require dirname(__FILE__) . '/../../../../lib/Horde/Text/Filter/Environment.php';

$env = <<<EOT
Simple line
Inline %FOO% variable
%FOO% at start
at end %FOO%
# %COMMENT% line
Variable %FOO% with # comment %COMMENT%
Simple line
EOT;

putenv('FOO=bar');
putenv('COMMENT=comment');
echo Horde_Text_Filter::filter($env, 'environment');

?>
--EXPECT--
Simple line
Inline bar variable
bar at start
at end bar
Variable bar with 
Simple line
