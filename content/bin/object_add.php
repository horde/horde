<?php

define('AUTH_HANDLER', true);
require dirname(__FILE__) . '/../lib/base.php';
require $CONTENT_DIR . 'lib/Objects/Object.php';
require $CONTENT_DIR . 'lib/Objects/ObjectMapper.php';

$options = array(
    new Horde_Argv_Option('-i', '--id', array('type' => 'int')),
    new Horde_Argv_Option('-t', '--type-id', array('type' => 'int')),
);
$parser = new Horde_Argv_Parser(array('optionList' => $options));
list($opts, $positional) = $parser->parseArgs();

if (!$opts->id || !$opts->type_id) {
    throw new InvalidArgumentException('id and type-id are both required');
}

$m = new Content_ObjectMapper;
$i = $m->create(array('object_name' => $opts->id,
                      'type_id' => $opts->type_id,
               ));
echo 'Created new object with id ' . $i->object_id . ' for ' . $i->type_id . ':' . $i->object_name . ".\n";
exit(0);
