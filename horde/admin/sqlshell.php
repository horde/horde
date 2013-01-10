<?php
/**
 * SQL shell.
 *
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:sqlshell')
));

$db = $injector->getInstance('Horde_Db_Adapter');
$q_cache = $session->get('horde', 'sql_query_cache', Horde_Session::TYPE_ARRAY);
$title = _("SQL Shell");
$vars = $injector->getInstance('Horde_Variables');

if ($vars->get('list-tables')) {
    $description = 'LIST TABLES';
    $result = $db->tables();
    $command = null;
} elseif ($command = trim($vars->sql)) {
    // Keep a cache of prior queries for convenience.
    if (($key = array_search($command, $q_cache)) !== false) {
        unset($q_cache[$key]);
    }
    $q_cache[] = $command;
    $q_cache = array_slice($q_cache, -20);
    $session->set('horde', 'sql_query_cache', $q_cache);

    // Parse out the query results.
    try {
        $result = $db->select(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
    } catch (Horde_Db_Exception $e) {
        $notification->push($e);
    }
}

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Text');

$view->action = Horde::url('admin/sqlshell.php');
$view->command = $command;
$view->q_cache = $q_cache;
$view->title = $title;

if (isset($result)) {
    $keys = null;
    $rows = array();
    $view->results = true;

    if (is_object($result) && $result->columnCount()) {
        while ($row = $result->fetch(Horde_Db::FETCH_ASSOC)) {
            if (is_null($keys)) {
                $keys = array();
                foreach ($row as $key => $val) {
                    $keys[] = Horde_String::convertCharset($key, $conf['sql']['charset'], 'UTF-8');
                }
            }

            $tmp = array();
            foreach ($row as $val) {
                $tmp[] = Horde_String::convertCharset($val, $conf['sql']['charset'], 'UTF-8');
            }
            $rows[] = $tmp;
        }
    } elseif (is_array($result)) {
        foreach ($result as $val) {
            if (is_null($keys)) {
                $keys[] = isset($description) ? $description : '';
            }
            $rows[] = array(
                Horde_String::convertCharset($val, $conf['sql']['charset'], 'UTF-8')
            );
        }
    }

    if (is_null($keys)) {
        $view->success = true;
    } else {
        $view->keys = $keys;
        $view->rows = $rows;
    }
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require HORDE_TEMPLATES . '/admin/menu.inc';
echo $view->render('sqlshell');
$page_output->footer();
