<?php
/**
 * Ingo basic view.
 *
 * Base URL Parameters:
 *   - page: (string) The current page view.
 *
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

$vars = $injector->getInstance('Horde_Variables');

$class = 'Ingo_Basic_' . Horde_String::ucfirst($vars->page);
if (!class_exists($class)) {
    throw new Ingo_Exception('Page not found: ' . $vars->page);
}

$ob = new $class($vars);

$status = $ob->status();

$page_output->header(array(
    'title' => $ob->title,
    'view' => $registry::VIEW_BASIC
));

echo $status;
$ob->render();

$page_output->footer();
