<?php
/**
 * Cache management.
 *
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:cache')
));

$cache = $injector->getInstance('Horde_Cache');
$vars = $injector->getInstance('Horde_Variables');

if ($vars->clearcache) {
    try {
        $cache->clear();
        $notification->push(
            _("Cache data cleared. NOTE: This does not indicate that cache data was successfully cleared on the backend, only that no error messages were returned."),
            'cli.success'
        );
    } catch (Horde_Exception $e) {
        $notification->push($e, 'horde.error');
    }
}

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Text');

$view->action = Horde::url('admin/cache.php');
$view->driver = $injector->getInstance('Horde_Core_Factory_Cache')->getDriverName();

$view->rw = $cache->testReadWrite();

$page_output->header(array(
    'title' => _("Cache Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('cache');
$page_output->footer();
