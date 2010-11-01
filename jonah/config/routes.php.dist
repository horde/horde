<?php
/**
 * Setup default routes
 */
$m->connect('Admin', '/admin/:section/:page', array(
     'controller' => 'admin',
     'section' => '',
     'page' => '',
));

// Valid filter names are "author", "tag" and "date"
// @TODO represent those with route requirements
$m->connect('Default', '/feeds/:feed/:filter/:value', array(
    'controller' => 'feed',
    'feed' => '',
    'filter' => '',
    'value' => '',
));

// api endpoint for getting post counts?
//$m->connect('/feeds/:feed/-/posts/count', array(
//    'controller' => 'api',
//    'action' => 'count'));


// Local route overrides
if (file_exists(dirname(__FILE__) . '/routes.local.php')) {
    include dirname(__FILE__) . '/routes.local.php';
}
