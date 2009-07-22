<?php

$horde_authentication = 'none';
require_once dirname(__FILE__) . '/../lib/base.php';

$v = new Horde_View(array('templatePath' => dirname(__FILE__) . '/garland'));
new Horde_View_Helper_Block($v);

$v->left = array(array('flexdemo', 'block1'),
                 array('flexdemo', 'block2'));
$v->app = array('list');

echo $v->render('main.html.php');
