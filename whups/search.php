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

/**
 * Construct the URL back to a supplied search
 */
function _getSearchUrl($vars)
{
    $qUrl = new Horde_Url();

    $queue = (int)$vars->get('queue');
    $qUrl->add(array('queue' => $queue));

    $summary = $vars->get('summary');
    if ($summary) {
        $qUrl->add('summary', $summary);
    }

    $states = $vars->get('states');
    if (is_array($states)) {
        foreach ($states as $type => $state) {
            if (is_array($state)) {
                foreach ($state as $s) {
                    $qUrl->add("states[$type][]", $s);
                }
            } else {
                $qUrl->add("states[$type]", $state);
            }
        }
    }

    return substr($qUrl, 1);
}

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('whups');

$renderer = new Horde_Form_Renderer();
$beendone = false;
$vars = Horde_Variables::getDefaultVariables();

// Update sorting preferences.
if (Horde_Util::getFormData('sortby') !== null) {
    $prefs->setValue('sortby', Horde_Util::getFormData('sortby'));
}
if (Horde_Util::getFormData('sortdir') !== null) {
    $prefs->setValue('sortdir', Horde_Util::getFormData('sortdir'));
}

$form = new Whups_Form_Search($vars);
$results = null;
if (($vars->get('formname') || $vars->get('summary') || $vars->get('states') ||
     Horde_Util::getFormData('haveSearch', false)) && $form->validate($vars, true)) {

    $form->getInfo($vars, $info);
    if ($vars->get('submitbutton') == _("Save as Query")) {
        $qManager = new Whups_Query_Manager();
        $whups_query = $qManager->newQuery();
        if (strlen($info['summary'])) {
            $whups_query->insertCriterion('', Whups_Query::CRITERION_SUMMARY, null,
                                          Whups_Query::OPERATOR_CI_SUBSTRING, $info['summary']);
        }
        if ($vars->get('queue')) {
            $whups_query->insertCriterion('', Whups_Query::CRITERION_QUEUE, null,
                                          Whups_Query::OPERATOR_EQUAL, $info['queue']);
        }
        foreach (array('ticket_timestamp', 'date_updated', 'date_resolved', 'date_assigned', 'date_due') as $date_field) {
            if (!empty($info[$date_field]['from']) || !empty($info[$date_field]['to'])) {
                $path = $whups_query->insertBranch('', Whups_Query::TYPE_AND);
                break;
            }
        }
        if (!empty($info['ticket_timestamp']['from'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_TIMESTAMP, null,
                                          Whups_Query::OPERATOR_GREATER, $info['ticket_timestamp']['from']);
        }
        if (!empty($info['ticket_timestamp']['to'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_TIMESTAMP, null,
                                          Whups_Query::OPERATOR_LESS, $info['ticket_timestamp']['to']);
        }
        if (!empty($info['date_updated']['from'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_UPDATED, null,
                                          Whups_Query::OPERATOR_GREATER, $info['date_updated']['from']);
        }
        if (!empty($info['date_updated']['to'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_UPDATED, null,
                                          Whups_Query::OPERATOR_LESS, $info['date_updated']['to']);
        }
        if (!empty($info['date_resolved']['from'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_RESOLVED, null,
                                          Whups_Query::OPERATOR_GREATER, $info['date_resolved']['from']);
        }
        if (!empty($info['date_resolved']['to'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_RESOLVED, null,
                                          Whups_Query::OPERATOR_LESS, $info['date_resolved']['to']);
        }
        if (!empty($info['date_assigned']['from'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_ASSIGNED, null,
                                          Whups_Query::OPERATOR_GREATER, $info['date_assigned']['from']);
        }
        if (!empty($info['date_assigned']['to'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_ASSIGNED, null,
                                          Whups_Query::OPERATOR_LESS, $info['date_assigned']['to']);
        }
        if (!empty($info['date_due']['from'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_DUE, null,
                                          Whups_Query::OPERATOR_GREATER, $info['date_due']['from']);
        }
        if (!empty($info['date_due']['to'])) {
            $whups_query->insertCriterion($path, Whups_Query::CRITERION_DUE, null,
                                          Whups_Query::OPERATOR_LESS, $info['date_due']['to']);
        }
        if ($info['state_id']) {
            $path = $whups_query->insertBranch('', Whups_Query::TYPE_OR);
            foreach ($info['state_id'] as $state) {
                $whups_query->insertCriterion($path, Whups_Query::CRITERION_STATE, null,
                                              Whups_Query::OPERATOR_EQUAL, $state);
            }
        }
        $session->set('whups', 'query', $whups_query);
        Horde::url('query/index.php', true)
            ->add('action', 'save')
            ->redirect();
    }
    try {
        $tickets = $whups_driver->getTicketsByProperties($info);
        Whups::sortTickets($tickets);
        $session->set('whups', 'last_search', Horde::url('search.php?' . _getSearchUrl($vars)));
        $results = new Whups_View_Results(
            array('title' => _("Search Results"),
                  'results' => $tickets,
                  'values' => Whups::getSearchResultColumns(),
                  'url' => $session->get('whups', 'last_search')));
        $beendone = true;
    } catch (Whups_Exception $e) {
        $notification->push(sprintf(_("There was an error performing your search: %s"), $tickets->getMessage()), 'horde.error');
    }
}

$page_output->header(array(
    'title' => _("Search")
));
require WHUPS_TEMPLATES . '/menu.inc';

if ($results) {
    $results->html();
    if (is_object($form)) {
        $form->setTitle(_("Refine Search"));
        $form->renderActive($renderer, $vars, Horde::url('search.php'), 'get');
        echo '<br />';
    }
}

if (!$beendone) {
    // Front search page.
    $form->setTitle(_("Ticket Search"));
    $form->renderActive($renderer, $vars, Horde::url('search.php'), 'get');
    echo '<br class="spacer" />';
}

$qManager = new Whups_Query_Manager();
$myqueries = new Whups_View_SavedQueries(
    array('title' => $GLOBALS['registry']->getAuth() ? _("My Queries") : _("Public Queries"),
          'results' => $qManager->listQueries($GLOBALS['registry']->getAuth(), true)));
$myqueries->html();

$page_output->footer();
