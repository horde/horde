<?php
/**
 * Hashtable management.
 *
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:hashtable')
));

$ht = $injector->getInstance('Horde_HashTable');
$vars = $injector->getInstance('Horde_Variables');

if ($vars->clearht) {
    $ht->clear();
    $notification->push(
        _("Hashtable data cleared"),
        'cli.success'
    );
}

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Text');

$view->action = Horde::url('admin/hashtable.php');
$view->driver = get_class($ht);
$view->locking = $ht->locking;
$view->persistent = $ht->persistent;

$test_key = '__horde_ht_admin_test';
$ht->delete($test_key);
$view->rw = ($ht->set($test_key, 'test') && ($ht->get($test_key) === 'test'));
$ht->delete($test_key);

$page_output->header(array(
    'title' => _("Hashtable Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('hashtable');
$page_output->footer();
