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

// Load the current query. If we have a 'slug' or 'query' parameter, that
// overrides and we load in that from the query store. Slug is tried
// first. Otherwise we use the query that is currently in our session.
$whups_query = null;
try {
    if ($vars->exists('slug')) {
        $whups_query = $qManager->getQueryBySlug($vars->get('slug'));
    } elseif ($vars->exists('query')) {
        $whups_query = $qManager->getQuery($vars->get('query'));
    } else {
        $whups_query = $session->get('whups', 'query');
    }
} catch (Whups_Exception $e) {
    $notification->push($e->getMessage());
}

// If we have an error, or if we still don't have a query, or if we don't have
// read permissions on the requested query, go to the initial Whups page.
if (!isset($whups_query) ||
    !$whups_query->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    if (isset($whups_query)) {
        $notification->push(_("Permission denied."), 'horde.error');
    }
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

// Query actions.
$tabs = $whups_query->getTabs($vars);

$renderer = new Horde_Form_Renderer();

// Update sorting preferences.
if (Horde_Util::getFormData('sortby') !== null) {
    $prefs->setValue('sortby', Horde_Util::getFormData('sortby'));
}
if (Horde_Util::getFormData('sortdir') !== null) {
    $prefs->setValue('sortdir', Horde_Util::getFormData('sortdir'));
}

$tickets = null;
$isvalid = false;
if (!$whups_query->parameters) {
    $isvalid = true;
} else {
    $form = new Whups_Form_Query_Parameter($whups_query, $vars);
    if ($vars->get('formname') == 'Whups_Form_Query_Parameter') {
        $isvalid = $form->validate($vars);
    }
}

if ($isvalid) {
    $tickets = $whups_driver->executeQuery($whups_query, $vars);
    $session->set('whups', 'last_search', Horde::url('query/run.php'));
}

if ($whups_query->id) {
    $page_output->addLinkTag($whups_query->feedLink());
}

$page_output->header(array(
    'title' => $whups_query->name ? $whups_query->name : _("Query Results")
));
require WHUPS_TEMPLATES . '/menu.inc';

echo $tabs->render($vars->get('action') ? $vars->get('action') : 'run');

if (!is_null($tickets)) {
    Whups::sortTickets($tickets);
    $subscription = null;
    if (isset($whups_query->id)) {
        $params = empty($whups_query->slug)
            ? array('id' => $whups_query->id)
            : array('slug' => $whups_query->slug);
        $subscription = Horde::link(Whups::urlFor('query_rss', $params, true, -1),
                                    _("Subscribe to this query"))
            . Horde::img('feed.png', _("Subscribe to this query"))
            . '</a>';
    }
    $results = new Whups_View_Results(
        array('title' => $title,
              'results' => $tickets,
              'extra' => $subscription,
              'values' => Whups::getSearchResultColumns(),
              'url' => Horde::url('query/run.php')));

    $results->html();
} else {
    $form->open($renderer, $vars, 'query/run.php');
    $renderer->beginActive($form->getTitle());
    $renderer->renderFormActive($form, $vars);
    $renderer->submit(_("Execute Query"));
    $renderer->end();
    $form->close($renderer);
}

$page_output->footer();

$session->set('whups', 'query', $whups_query);
