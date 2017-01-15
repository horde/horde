<?php
/**
 * Copyright 2004-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Jason M. Felice <jason.m.felice@gmail.com>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('hermes');

if (Hermes::showAjaxView()) {
    Horde::url('', true)->setAnchor('search')->redirect();
}

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
    try {
        $vars->set('actionID', 'search_export');
        $registry->callAppMethod('hermes', 'download', array('args' => array($vars)));
    } catch (Horde_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
}

$title = _("Search for Time");
if (!($searchVars = $session->get('hermes', 'search_criteria'))) {
    $searchVars = $vars;
}
$form = new Hermes_Form_Search($searchVars);

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
$form->renderActive(new Horde_Form_Renderer(), $searchVars, Horde::url('search.php'), 'post');
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

echo '<br />';
$exportForm = new Hermes_Form_Export($vars);
$exportForm->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('search.php'), 'post');

$page_output->footer();
