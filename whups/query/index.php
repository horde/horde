<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$vars = Horde_Variables::getDefaultVariables();
$qManager = new Whups_Query_Manager();

// Set up the page config vars.
$showEditQuery = true;
$showExtraForm = null;

// Find our current query.
if ($whups_query = $session->get('whups', 'query')) {
    if (!$whups_query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
        $notification->push(_("Permission denied."), 'horde.error');
        Horde::url($prefs->getValue('whups_default_view') . '.php', true)
            ->redirect();
    }
} else {
    $whups_query = $qManager->newQuery();
}

// Find the current criteria form, and default to the user form if not
// present.
if (!$session->exists('whups', 'query_form')) {
    $session->set('whups', 'query_form', 'props');
}
$vars->set('whups_query_form', $session->get('whups', 'query_form'));

// What now? First check the result of the query edit action dropdown, as this
// action overrides the form it sits within.
if ($vars->get('qaction1') || $vars->get('qaction2')) {
    $action = $vars->get('qaction1') ? $vars->get('qaction1') : $vars->get('qaction2');

    switch ($action) {
    // Query actions.
    case 'deleteNode':
        $whups_query->deleteNode($vars->get('path'));
        $vars->remove('path');
        break;

    case 'hoist':
        $whups_query->hoist($vars->get('path'));
        break;

    case 'branch':
        $form = new Whups_Form_InsertBranch($vars);
        break;

    case 'not':
        $path = $whups_query->insertBranch($vars->get('path'), Whups_Query::TYPE_NOT);
        $vars->set('path', $path);
        break;

    case 'and':
        $path = $whups_query->insertBranch($vars->get('path'), Whups_Query::TYPE_AND);
        $vars->set('path', $path);
        break;

    case 'or':
        $path = $whups_query->insertBranch($vars->get('path'), Whups_Query::TYPE_OR);
        $vars->set('path', $path);
        break;

    case 'edit':
        try {
            $qf = $whups_query->pathToForm($vars);
        } catch (Whups_Exception $e) {
            $notification->push($e->getMessage());
            $qf = 'props';
        }
        $session->set('whups', 'query_form', 'props');
        $vars->set('edit', true);
        break;
    }

    $vars->remove('qaction1');
    $vars->remove('qaction2');
} elseif ($vars->get('formname')) {
    // Now check for submitted forms.
    $class = $vars->get('formname');
    $form = new $class($vars);
    if ($form->validate($vars)) {
        if ($vars->get('edit')) {
            $whups_query->deleteNode($vars->get('path'));
            $path = Whups_Query::stringToPath($vars->get('path'));
            array_pop($path);
            $vars->set('path', Whups_Query::pathToString($path));
            $vars->remove('edit');
        }
        $form->execute($vars);
        $vars->remove('action');
    }
} elseif ($vars->get('action') != '') {
    // Last, check for actions from tabs.
    $action = $vars->get('action');

    switch ($action) {
    // Current form actions.
    case 'props':
    case 'user':
    case 'group':
    case 'date':
    case 'text':
    case 'attribs':
        $session->set('whups', 'query_form', $action);
        break;

    // Global query options
    case 'new':
        unset($whups_query);
        $whups_query = $qManager->newQuery();
        break;

    case 'delete':
        $showExtraForm = 'Whups_Form_Query_Delete';
        $showEditQuery = false;
        break;

    case 'save':
        $showExtraForm = 'Whups_Form_Query_ChooseNameForSave';
        $showEditQuery = false;
        break;

    case 'load':
        $showExtraForm = 'Whups_Form_Query_ChooseNameForLoad';
        $showEditQuery = false;
        break;
    }
}

// Query actions.
$queryTabs = $whups_query->getTabs($vars);

// Criterion form types.
$queryurl = Horde::url('query/index.php');
$vars->set('action', $session->get('whups', 'query_form'));
$criteriaTabs = new Horde_Core_Ui_Tabs('action', $vars);
$criteriaTabs->preserve('path', $vars->get('path'));
$criteriaTabs->addTab(_("_Property Criteria"), $queryurl, 'props');
$criteriaTabs->addTab(_("_User Criteria"), $queryurl, 'user');
$criteriaTabs->addTab(_("_Group Criteria"), $queryurl, 'group');
$criteriaTabs->addTab(_("_Date Criteria"), $queryurl, 'date');
$criteriaTabs->addTab(_("_Text Criteria"), $queryurl, 'text');
$criteriaTabs->addTab(_("Attri_bute Criteria"), $queryurl, 'attribs');

$qops = array(
    ''           => _("Choose Action:"),
    'deleteNode' => _("Delete"),
    'edit'       => _("Edit"),
    'hoist'      => _("Hoist Children"),
    'and'        => _("Insert And"),
    'or'         => _("Insert Or"),
    'not'        => _("Insert Not"),
);

// Start the page.
if ($whups_query->id) {
    $page_output->addLinkTag($whups_query->feedLink());
}

$page_output->header(array(
    'title' => _("Query Builder")
));
require WHUPS_TEMPLATES . '/menu.inc';

echo $queryTabs->render(Horde_Util::getFormData('action', 'edit'));

if ($showExtraForm !== null) {
    $form = new $showExtraForm($vars);
    $form->renderActive($form->getRenderer(), $vars, Horde::url('query/index.php'));
    echo '<br class="spacer" />';
}

/* Get the general query renderer object. */
$queryRenderer = new Whups_Form_Renderer_Query();

if ($showEditQuery) {
    // Get our current form.
    switch ($session->get('whups', 'query_form')) {
    default:
        printf(_("Error: Unknown query form \"%s\", defaulting to properties"),
               $session->get('whups', 'query_form'));
        // Fall through.

    case 'props':
        $form = new Whups_Form_Query_PropertyCriterion($vars);
        break;

    case 'user':
        $form = new Whups_Form_Query_UserCriterion($vars);
        break;

    case 'group':
        $form = new Whups_Form_Query_GroupCriterion($vars);
        break;

    case 'text':
        $form = new Whups_Form_Query_TextCriterion($vars);
        break;

    case 'attribs':
        $form = new Whups_Form_Query_AttributeCriterion($vars);
        break;

    case 'date':
        $form = new Whups_Form_Query_DateCriterion($vars);
        break;
    }

    $renderer = $form->getRenderer();
    $form->open($renderer, $vars, 'index.php', 'post');

    $queryRenderer->beginActive(_("Current Query"));
    $queryRenderer->edit($qops, $form->getName(), 1);
    $queryRenderer->renderFormActive($whups_query, $vars);
    $queryRenderer->edit($qops, $form->getName(), 2);
    $renderer->end();

    echo '<br />' . $criteriaTabs->render();

    $renderer->beginActive($form->getTitle());
    $renderer->renderFormActive($form, $vars);
    $renderer->submit($vars->get('edit') ? _("Save Criterion") : _("Add Criterion"));
    $renderer->end();

    $form->close($renderer);
} else {
    // Show query readonly.
    $renderer = new Horde_Form_Renderer();
    $renderer->beginActive(_("Current Query"));
    $queryRenderer->renderFormInactive($whups_query, $vars);
    $renderer->end();
}

$page_output->footer();

$session->set('whups', 'query', $whups_query);
