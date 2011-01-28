<?php
/**
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jason M. Felice <jason.m.felice@gmail.com>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

$vars = Horde_Variables::getDefaultVariables();
$delete = $vars->get('delete');
if (!empty($delete)) {
    try {
        $GLOBALS['injector']->getInstance('Hermes_Driver')->updateTime(array(array('id' => $delete, 'delete' => true)));
    } catch (Horde_Exception $e) {
        $notification->push(sprintf(_("There was an error deleting the time: %s"), $e->getMessage()), 'horde.error');
    }
    $notification->push(_("The time entry was successfully deleted."), 'horde.success');
    $vars->remove('delete');
}

$criteria = null;

$formname = $vars->get('formname');
switch ($formname) {
case 'hermes_form_search':
    $form = new Hermes_Form_Search($vars);
    $form->validate($vars);
    $criteria = $form->getSearchCriteria($vars);
    if (is_null($criteria)) {
        $session->remove('hermes', 'search_criteria');
    } else {
        $session->set('hermes', 'search_criteria', $vars);
    }
    break;

case 'hermes_form_export':
    if (!($searchVars = $session->get('hermes', 'search_criteria'))) {
        $notification->push(_("No search to export!"), 'horde.error');
    } else {
        $searchForm = new Hermes_Form_Search($searchVars);
        $criteria = $searchForm->getSearchCriteria($searchVars);
        if (is_null($criteria)) {
            $notification->push(_("No search to export!"), 'horde.error');
        } else {
            $form = new Hermes_Form_Export($vars);
            $form->validate($vars);
            if ($form->isValid()) {
                $form->getInfo($vars, $info);
                try {
                    $hours = $GLOBALS['injector']->getInstance('Hermes_Driver')->getHours($criteria);
                    if (is_null($hours) || count($hours) == 0) {
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
                            $GLOBALS['injector']->getInstance('Hermes_Driver')->markAs('exported', $hours);
                        }
                        exit;
                    }
                } catch (Horde_Exception $e) {
                    $notification->push($hours, 'horde.error');
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
require $registry->get('templates', 'horde') . '/common-header.inc';

if (!($searchVars = $session->get('hermes', 'search_criteria'))) {
    $searchVars = $vars;
}
$form = new Hermes_Form_Search($searchVars);

$print_link = Horde::url(Horde_Util::addParameter('search.php', array('print' => 'true')));
require HERMES_TEMPLATES . '/menu.inc';
$form->renderActive(new Horde_Form_Renderer(), $searchVars, 'search.php', 'post');
echo '<br />';

if ($session->exists('hermes', 'search_criteria')) {
    echo Hermes::tabs();

    if (is_null($criteria)) {
        $criteria = $form->getSearchCriteria($searchVars);
    }

    $table = new Hermes_Table('results', $vars,
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
    $exportForm = new Hermes_Form_Export($vars);
    $exportForm->renderActive(new Horde_Form_Renderer(), $vars, 'search.php', 'post');
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
