<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jason M. Felice <jason.m.felice@gmail.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hermes = Horde_Registry::appInit('hermes');
require_once HERMES_BASE . '/lib/Forms/Export.php';
require_once HERMES_BASE . '/lib/Forms/Search.php';
require_once HERMES_BASE . '/lib/Forms/Time.php';
require_once HERMES_BASE . '/lib/Table.php';

$vars = Horde_Variables::getDefaultVariables();

$delete = $vars->get('delete');
if (!empty($delete)) {
    $result = $hermes->driver->updateTime(array(array('id' => $delete, 'delete' => true)));
    if (is_a($result, 'PEAR_Error')) {
        Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
        $notification->push(sprintf(_("There was an error deleting the time: %s"), $result->getMessage()), 'horde.error');
    } else {
        $notification->push(_("The time entry was successfully deleted."), 'horde.success');
        $vars->remove('delete');
    }
}

$criteria = null;

$formname = $vars->get('formname');
switch ($formname) {
case 'searchform':
    $form = new SearchForm($vars);
    $form->validate($vars);
    $criteria = $form->getSearchCriteria($vars);
    if (is_null($criteria)) {
        unset($_SESSION['hermes_search_criteria']);
    } else {
        $_SESSION['hermes_search_criteria'] = serialize($vars);
    }
    break;

case 'exportform':
    if (!isset($_SESSION['hermes_search_criteria'])) {
        $notification->push(_("No search to export!"), 'horde.error');
    } else {
        $searchVars = unserialize($_SESSION['hermes_search_criteria']);
        $searchForm = new SearchForm($searchVars);
        $criteria = $searchForm->getSearchCriteria($searchVars);
        if (is_null($criteria)) {
            $notification->push(_("No search to export!"), 'horde.error');
        } else {
            $form = new ExportForm($vars);
            $form->validate($vars);
            if ($form->isValid()) {
                $form->getInfo($vars, $info);
                $hours = $hermes->driver->getHours($criteria);
                if (is_a($hours, 'PEAR_Error')) {
                    $notification->push($hours, 'horde.error');
                } elseif (is_null($hours) || count($hours) == 0) {
                    $notification->push(_("No time to export!"), 'horde.error');
                } else {
                    $exportHours = Hermes::makeExportHours($hours);
                    $data = Horde_Data::factory(array('hermes', $info['format']));
                    $filedata = $data->exportData($exportHours);
                    $browser->downloadHeaders($data->getFilename('export'), $data->getContentType(), false, strlen($filedata));

                    echo $filedata;
                    if (!empty($info['mark_exported']) &&
                        $info['mark_exported'] == 'yes' &&
                        $perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(),
                                              Horde_Perms::EDIT)) {
                        $hermes->driver->markAs('exported', $hours);
                    }
                    exit;
                }
            }
        }
    }
}


$title = _("Search for Time");
$print_view = (bool)$vars->get('print');
if (!$print_view) {
    Horde::addScriptFile('popup.js', 'horde', true);
}
require HERMES_TEMPLATES . '/common-header.inc';

if (isset($_SESSION['hermes_search_criteria'])) {
    $searchVars = unserialize($_SESSION['hermes_search_criteria']);
} else {
    $searchVars = $vars;
}
$form = new SearchForm($searchVars);

$print_link = Horde::url(Horde_Util::addParameter('search.php', array('print' => 'true')));
require HERMES_TEMPLATES . '/menu.inc';
$form->renderActive(new Horde_Form_Renderer(), $searchVars, 'search.php', 'post');
echo '<br />';

if (isset($_SESSION['hermes_search_criteria'])) {
    echo Hermes::tabs();

    if (is_null($criteria)) {
        $criteria = $form->getSearchCriteria($searchVars);
    }

    $table = new Horde_Core_Ui_Table('results', $vars,
                                array('title' => _("Search Results"),
                                      'name' => 'hermes/hours',
                                      'params' => $criteria));

    $template = new Horde_Template();
    $template->setOption('gettext', true);
    $template->set('postUrl', Horde::url('time.php', false, -1));
    $template->set('sessionId', Horde_Util::formInput());
    $template->set('table', $table->render());

    echo $template->fetch(HERMES_TEMPLATES . '/time/form.html');
}

if (!$print_view) {
    echo '<br />';
    $exportForm = new ExportForm($vars);
    $exportForm->renderActive(new Horde_Form_Renderer(), $vars, 'search.php', 'post');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
