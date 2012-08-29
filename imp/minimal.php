<?php
/**
 * IMP minimal view.
 *
 * Base URL Parameters:
 *   - page: (string) The current page view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
$vars = Horde_Variables::getDefaultVariables();
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_MINIMAL,
    'timezone' => in_array($vars->page, array('compose', 'mailbox', 'message'))
));

$class = 'IMP_Minimal_' . Horde_String::ucfirst($vars->page);
if (!class_exists($class)) {
    throw new IMP_Exception('Page not found: ' . $vars->page);
}

$ob = new $class($vars);

$page_output->header(array(
    'title' => $ob->title,
    'view' => $registry::VIEW_MINIMAL
));

IMP::status();
$ob->render();

$page_output->footer();
