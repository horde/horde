<?php
/**
 * Copyright 2002-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ingo');

/* Redirect if script updating is not available. */
if (!$injector->getInstance('Ingo_Factory_Script')->hasFeature('script_file')) {
    Horde::url('filters.php', true)->redirect();
}

/* Generate the script. */
$scripts = array();
foreach ($injector->getInstance('Ingo_Factory_Script')->createAll() as $script) {
    $scripts = array_merge($scripts, $script->generate());
}

/* Activate/deactivate script if requested. */
$actionID = Horde_Util::getFormData('actionID');
switch ($actionID) {
case 'action_activate':
    if (!empty($scripts)) {
        try {
            Ingo::activateScripts($scripts, false);
        } catch (Ingo_Exception $e) {
            $notification->push($e);
        }
    }
    break;

case 'action_deactivate':
    try {
        Ingo::activateScripts('', true);
    } catch (Ingo_Exception $e) {
        $notification->push($e);
    }
    break;

case 'show_active':
    $scripts = array();
    foreach ($session->get('ingo', 'backend/transport', Horde_Session::TYPE_ARRAY) as $transport) {
        try {
            $backend = $injector->getInstance('Ingo_Factory_Transport')
                ->create($transport);
            if (method_exists($backend, 'getScript')) {
                $scripts[] = $backend->getScript();
            }
        } catch (Horde_Exception_NotFound $e) {
        } catch (Ingo_Exception $e) {
            $notification->push($e);
        }
    }
    break;
}

/* Prepare the view. */
$view = new Horde_View(array(
    'templatePath' => INGO_TEMPLATES . '/basic/script'
));
$view->addHelper('Text');

if (empty($scripts)) {
    $view->scriptexists = false;
} else {
    $view->scriptexists = true;
    foreach ($scripts as &$script) {
        $script['lines'] = preg_split('(\r\n|\n|\r)', $script['script']);
        $script['width'] = strlen(count($script['lines']));
    }
}
$view->scripturl = Horde::url('script.php');
$view->showactivate = ($actionID != 'show_active');
if ($view->scriptexists) {
    $view->scripts = $scripts;
}

$page_output->header(array(
    'title' => _("Filter Script Display")
));
Ingo::status();
echo $view->render('script');
$page_output->footer();
