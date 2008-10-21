<?php

define('AUTH_HANDLER', true);
define('HORDE_BASE', dirname(__FILE__) . '/..');
require_once HORDE_BASE . '/lib/base.php';
require_once 'Horde/Autoloader.php';

$v = new Horde_View(array('templatePath' => dirname(__FILE__) . '/garland'));
new Horde_View_Helper_Block($v);

$v->left = array(array('flexdemo', 'block1'),
                 array('flexdemo', 'block2'));
$v->app = array('list');

echo $v->render('main.html.php');
