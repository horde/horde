<?php

// Admin index
$mapper->connect('admin', array('controller' => 'admin'));

// Default route for serving content
$mapper->connect('page', ':page', array('controller' => 'page'));


// Local route overrides
if (file_exists(dirname(__FILE__) . '/routes.local.php')) {
    include dirname(__FILE__) . '/routes.local.php';
}
