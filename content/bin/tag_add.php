<?php

define('AUTH_HANDLER', true);
require $CONTENT_DIR . 'lib/Tags/Tag.php';
require $CONTENT_DIR . 'lib/Tags/TagMapper.php';

$parser = new Horde_Argv_Parser();
list($opts, $tags) = $parser->parseArgs();
if (!count($tags)) {
    throw new InvalidArgumentException('List at least one tag to add.');
}

$m = new Content_TagMapper;
foreach ($tags as $tag) {
    $t = $m->create(array('tag_name' => $tag));
    echo 'Created new tag with id ' . $t->tag_id . ' and name "' . $t->tag_name . "\".\n";
}
exit(0);
