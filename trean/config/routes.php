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

$mapper->connect('/tag/:tag',
    array(
        'controller' => 'BrowseByTag',
    ));
