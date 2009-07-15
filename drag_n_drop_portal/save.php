<?php
/**
 * $Id: block.php 219 2008-01-11 09:45:33Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
require_once dirname(__FILE__) . '/../lib/base.php';

$layout = array();
$params = Horde_Util::getPost('params');
foreach ($_POST as $column => $rows) {
    if (substr($column, 0, 11) != 'widget_col_') {
        continue;
    }
    $col = (int)substr($column, 11);
    foreach ($rows as $row => $widget) {
        $id = (int)substr($widget, 7);
        list($app, $name) = explode(':', $params[$id]['type']);
        $layout[$row][$col] = array('app' => $app,
                                    'height' => 1,
                                    'width' => 1,
                                    'params' => array('type' => $name,
                                                      'params' => $params[$id]));
    }
}

$prefs->setValue('portal_layout', serialize($layout));
