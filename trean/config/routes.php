<?php
/**
 * Setup default routes
 */
$mapper->connect('/b/save',
    array(
        'controller' => 'SaveBookmark',
    ));

$mapper->connect('/b/delete',
    array(
        'controller' => 'DeleteBookmark',
    ));
