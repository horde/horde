<?php

define('AUTH_HANDLER', true);

$options = array(
    new Horde_Argv_Option('-n', '--not-defined', array('type' => 'int')),
);
$parser = new Horde_Argv_Parser(array('optionList' => $options));
list($opts, $tags) = $parser->parseArgs();

if (!$opts->not_defined) {
    throw new InvalidArgumentException('');
}

exit(0);
