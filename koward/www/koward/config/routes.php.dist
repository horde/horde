<?php

$mapper->connect('index', array('controller' => 'index'));
$mapper->connect('index.php', array('controller' => 'index'));

$mapper->connect('check/:action/:id', array('controller' => 'check', 'action' => 'show'));
$mapper->connect(':controller/:action/:id', array('controller' => 'object'));

// Local route overrides
if (file_exists(dirname(__FILE__) . '/routes.local.php')) {
    include dirname(__FILE__) . '/routes.local.php';
}
