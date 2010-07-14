<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Query.php';
require_once WHUPS_BASE . '/lib/Forms/Query.php';
require_once WHUPS_BASE . '/lib/Renderer/Query.php';

$vars = Horde_Variables::getDefaultVariables();
$qManager = new Whups_QueryManager();

// Set up the page config vars.
$showEditQuery = true;
$showExtraForm = null;

// Find our current query.
if (isset($_SESSION['whups']['query'])) {
    $whups_query = unserialize($_SESSION['whups']['query']);
    if (!$whups_query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
        $notification->push(_("Permission denied."), 'horde.error');
        header('Location: ' . Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true));
        exit;
    }
} else {
    $whups_query = $qManager->newQuery();
}

// Find the current criteria form, and default to the user form if not
// present.
if (!isset($_SESSION['whups']['query_form'])) {
    $_SESSION['whups']['query_form'] = 'props';
}
$vars->set('whups_query_form', $_SESSION['whups']['query_form']);

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
        $form = new InsertBranchForm($vars);
        break;

    case 'not':
        $path = $whups_query->insertBranch($vars->get('path'), QUERY_TYPE_NOT);
        $vars->set('path', $path);
        break;

    case 'and':
        $path = $whups_query->insertBranch($vars->get('path'), QUERY_TYPE_AND);
        $vars->set('path', $path);
        break;

    case 'or':
        $path = $whups_query->insertBranch($vars->get('path'), QUERY_TYPE_OR);
        $vars->set('path', $path);
        break;

    case 'edit':
        $_SESSION['whups']['query_form'] = $whups_query->pathToForm($vars);
        if (is_a($_SESSION['whups']['query_form'], 'PEAR_Error')) {
            $notification->push($_SESSION['whups']['query_form']);
            $_SESSION['whups']['query_form'] = 'props';
        }
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
        $_SESSION['whups']['query_form'] = $action;
        break;

    // Global query options
    case 'new':
        unset($whups_query);
        $whups_query = $qManager->newQuery();
        break;

    case 'delete':
        $showExtraForm = 'DeleteQueryForm';
        $showEditQuery = false;
        break;

    case 'save':
        $showExtraForm = 'ChooseQueryNameForSaveForm';
        $showEditQuery = false;
        break;

    case 'load':
        $showExtraForm = 'ChooseQueryNameForLoadForm';
        $showEditQuery = false;
        break;
    }
}

// Query actions.
$queryTabs = $whups_query->getTabs($vars);

// Criterion form types.
$queryurl = Horde::applicationUrl('query/index.php');
$vars->set('action', $_SESSION['whups']['query_form']);
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
$title = _("Query Builder");
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';

echo $queryTabs->render(Horde_Util::getFormData('action', 'edit'));

if ($showExtraForm !== null) {
    $form = new $showExtraForm($vars);
    $form->renderActive($form->getRenderer(), $vars, 'index.php');
    echo '<br class="spacer" />';
}

/* Get the general query renderer object. */
$queryRenderer = new Horde_Form_Renderer_Query();

if ($showEditQuery) {
    // Get our current form.
    switch ($_SESSION['whups']['query_form']) {
    default:
        printf(_("Error: Unknown query form \"%s\", defaulting to properties"),
               $_SESSION['whups']['query_form']);
        // Fall through.

    case 'props':
        $form = new PropertyCriterionForm($vars);
        break;

    case 'user':
        $form = new UserCriterionForm($vars);
        break;

    case 'group':
        $form = new GroupCriterionForm($vars);
        break;

    case 'text':
        $form = new TextCriterionForm($vars);
        break;

    case 'attribs':
        $form = new AttributeCriterionForm($vars);
        break;

    case 'date':
        $form = new DateCriterionForm($vars);
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

require $registry->get('templates', 'horde') . '/common-footer.inc';

$_SESSION['whups']['query'] = serialize($whups_query);
