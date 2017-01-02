<?php
/**
 * SQL shell.
 *
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
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
$type = null;
if ($vars->get('list-tables') || ($command = trim($vars->sql))) {
    $session->checkToken($vars->token);
}
if ($vars->get('list-tables')) {
    $description = 'LIST TABLES';
    $result = $db->tables();
    sort($result);
    $command = null;
} elseif ($command = trim($vars->sql)) {
    // Keep a cache of prior queries for convenience.
    if (($key = array_search($command, $q_cache)) !== false) {
        unset($q_cache[$key]);
    }
    $q_cache[] = $command;
    $q_cache = array_slice($q_cache, -20);
    $session->set('horde', 'sql_query_cache', $q_cache);

    if (stripos($command, 'UPDATE') === 0) {
        $type = 'update';
        try {
            $result = $db->update(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
        } catch (Horde_Db_Exception $e) {
            $notification->push($e);
        }
    } elseif (stripos($command, 'INSERT') === 0) {
        $type = 'insert';
        try {
            $result = $db->insert(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
        } catch (Horde_Db_Exception $e) {
            $notification->push($e);
        }
    } elseif (stripos($command, 'DELETE') === 0) {
        $type = 'delete';
        try {
            $result = $db->delete(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
        } catch (Horde_Db_Exception $e) {
            $notification->push($e);
        }
    } else {
        // Default to a SELECT and hope for the best.
        $type = 'select';
        try {
            $result = $db->select(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
        } catch (Horde_Db_Exception $e) {
            $notification->push($e);
        }
    }
}

$view = new Horde_View(array(
    'templatePath' => HORDE_TEMPLATES . '/admin'
));
$view->session = $session;
$view->addHelper('Horde_Core_View_Helper_Help');
$view->addHelper('Text');

$view->action = Horde::url('admin/sqlshell.php');
$view->command = $command;
$view->q_cache = $q_cache;
$view->title = $title;

switch ($type) {
case 'insert':
    $notification->push(_("The INSERT command completed successfully."), 'horde.success');
    unset($result);
    break;
case 'update':
    $notification->push(sprintf(_("The UPDATE command completed successfully. A total of %d rows were modified."), $result), 'horde.success');
    unset($result);
    break;
case 'delete':
    $notification->push(sprintf(_("The DELETE command completed successfully. A total of %d rows were deleted."), $result), 'horde.success');
}

if (isset($result)) {
    $keys = null;
    $rows = array();
    $view->results = true;

    try {
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
    } catch (Horde_Db_Exception $e) {
        $notification->push($e);
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
