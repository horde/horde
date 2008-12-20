<?php

require_once dirname(__FILE__) . '/lib/base.php';
require_once $CONTENT_DIR . '/lib/Tags/Tagger.php';

$request = new Horde_Controller_Request_Http();

$mapper = new Horde_Routes_Mapper();
require $CONTENT_DIR . '/config/routes.php';

$context = array(
    'mapper' => $mapper,
    'controllerDir' => $CONTENT_DIR . '/app/controllers',
    'viewsDir' => $CONTENT_DIR . '/app/views',
    // 'logger' => '',
);

$dispatcher = Horde_Controller_Dispatcher::singleton($context);
$dispatcher->dispatch($request);
