<?php
/**
 * IMP basic view.
 *
 * Base URL Parameters:
 *   - page: (string) The current page view.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

require_once __DIR__ . '/lib/Application.php';

$vars = Horde_Variables::getDefaultVariables();
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_BASIC,
    'session_control' => ($vars->page == 'compose' ? 'netscape' : null),
    'timezone' => in_array($vars->page, array('compose', 'mailbox', 'message'))
));

$class = 'IMP_Basic_' . Horde_String::ucfirst($vars->page);
if (!class_exists($class)) {
    throw new IMP_Exception('Page not found: ' . $vars->page);
}

try {
    $ob = new $class($vars);
} catch (Exception $e) {
    if ($registry->getView() == $registry::VIEW_BASIC) {
        $notification->push($e);
        $ob = new IMP_Basic_Error($vars);
    } else {
        throw $e;
    }
}

$status = $ob->status();

$page_output->header(array_merge(array(
    'title' => $ob->title,
    'view' => $registry::VIEW_BASIC
), $ob->header_params));

echo $status;
$ob->render();

$page_output->footer();
