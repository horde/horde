<?php
/**
 * Setup default routes
 */
$mapper->connect('/t/complete',
    array(
        'controller' => 'CompleteTask',
    ));

$mapper->connect('/t/save',
    array(
        'controller' => 'SaveTask',
    ));
