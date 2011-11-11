<?php
/**
 * Setup default routes
 */
$mapper->connect('/c/complete.json',
    array(
        'controller' => 'complete',
        'format' => 'json',
    ));
