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

/**
 * Construct the URL back to a supplied search
 */
function _getSearchUrl($vars)
{
    $qUrl = '';

    $queue = (int)$vars->get('queue');
    $qUrl = Horde_Util::addParameter($qUrl, array('queue' => $queue));

    $summary = $vars->get('summary');
    if ($summary) {
        $qUrl = Horde_Util::addParameter($qUrl, 'summary', $summary);
    }

    $states = $vars->get('states');
    if (is_array($states)) {
        foreach ($states as $type => $state) {
            if (is_array($state)) {
                foreach ($state as $s) {
                    $qUrl = Horde_Util::addParameter($qUrl, "states[$type][]", $s);
                }
            } else {
                $qUrl = Horde_Util::addParameter($qUrl, "states[$type]", $state);
            }
        }
    }

    return substr($qUrl, 1);
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Query.php';
require_once WHUPS_BASE . '/lib/Forms/Search.php';

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

$form = new SearchForm($vars);
$results = null;
if (($vars->get('formname') || $vars->get('summary') || $vars->get('states') ||
     Horde_Util::getFormData('haveSearch', false)) && $form->validate($vars, true)) {

    $form->getInfo($vars, $info);
    if ($vars->get('submitbutton') == _("Save as Query")) {
        require_once WHUPS_BASE . '/lib/Query.php';
        $qManager = new Whups_QueryManager();
        $whups_query = $qManager->newQuery();
        if (strlen($info['summary'])) {
            $whups_query->insertCriterion('', CRITERION_SUMMARY, null,
                                          OPERATOR_CI_SUBSTRING, $info['summary']);
        }
        if ($vars->get('queue')) {
            $whups_query->insertCriterion('', CRITERION_QUEUE, null,
                                          OPERATOR_EQUAL, $info['queue']);
        }
        foreach (array('ticket_timestamp', 'date_updated', 'date_resolved', 'date_assigned', 'date_due') as $date_field) {
            if (!empty($info[$date_field]['from']) || !empty($info[$date_field]['to'])) {
                $path = $whups_query->insertBranch('', QUERY_TYPE_AND);
                break;
            }
        }
        if (!empty($info['ticket_timestamp']['from'])) {
            $whups_query->insertCriterion($path, CRITERION_TIMESTAMP, null,
                                          OPERATOR_GREATER, $info['ticket_timestamp']['from']);
        }
        if (!empty($info['ticket_timestamp']['to'])) {
            $whups_query->insertCriterion($path, CRITERION_TIMESTAMP, null,
                                          OPERATOR_LESS, $info['ticket_timestamp']['to']);
        }
        if (!empty($info['date_updated']['from'])) {
            $whups_query->insertCriterion($path, CRITERION_UPDATED, null,
                                          OPERATOR_GREATER, $info['date_updated']['from']);
        }
        if (!empty($info['date_updated']['to'])) {
            $whups_query->insertCriterion($path, CRITERION_UPDATED, null,
                                          OPERATOR_LESS, $info['date_updated']['to']);
        }
        if (!empty($info['date_resolved']['from'])) {
            $whups_query->insertCriterion($path, CRITERION_RESOLVED, null,
                                          OPERATOR_GREATER, $info['date_resolved']['from']);
        }
        if (!empty($info['date_resolved']['to'])) {
            $whups_query->insertCriterion($path, CRITERION_RESOLVED, null,
                                          OPERATOR_LESS, $info['date_resolved']['to']);
        }
        if (!empty($info['date_assigned']['from'])) {
            $whups_query->insertCriterion($path, CRITERION_ASSIGNED, null,
                                          OPERATOR_GREATER, $info['date_assigned']['from']);
        }
        if (!empty($info['date_assigned']['to'])) {
            $whups_query->insertCriterion($path, CRITERION_ASSIGNED, null,
                                          OPERATOR_LESS, $info['date_assigned']['to']);
        }
        if (!empty($info['date_due']['from'])) {
            $whups_query->insertCriterion($path, CRITERION_DUE, null,
                                          OPERATOR_GREATER, $info['date_due']['from']);
        }
        if (!empty($info['date_due']['to'])) {
            $whups_query->insertCriterion($path, CRITERION_DUE, null,
                                          OPERATOR_LESS, $info['date_due']['to']);
        }
        if ($info['state_id']) {
            $path = $whups_query->insertBranch('', QUERY_TYPE_OR);
            foreach ($info['state_id'] as $state) {
                $whups_query->insertCriterion($path, CRITERION_STATE, null,
                                              OPERATOR_EQUAL, $state);
            }
        }
        $_SESSION['whups']['query'] = serialize($whups_query);
        Horde::url('query/index.php', true)
            ->add('action', 'save')
            ->redirect();
    }
    $tickets = $whups_driver->getTicketsByProperties($info);
    if (is_a($tickets, 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error performing your search: %s"), $tickets->getMessage()), 'horde.error');
    } else {
        Whups::sortTickets($tickets);

        $_SESSION['whups']['last_search'] = Horde::url('search.php?' . _getSearchUrl($vars));
        $results = Whups_View::factory(
            'Results',
            array('title' => _("Search Results"),
                  'results' => $tickets,
                  'values' => Whups::getSearchResultColumns(),
                  'url' => $_SESSION['whups']['last_search']));
        $beendone = true;
    }
}

$title = _("Search");
require WHUPS_TEMPLATES . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';

if ($results) {
    $results->html();
    if (is_object($form)) {
        $form->setTitle(_("Refine Search"));
        $form->renderActive($renderer, $vars, 'search.php', 'get');
        echo '<br />';
    }
}

if (!$beendone) {
    // Front search page.
    $form->setTitle(_("Ticket Search"));
    $form->renderActive($renderer, $vars, 'search.php', 'get');
    echo '<br class="spacer" />';
}

$qManager = new Whups_QueryManager();
$myqueries = Whups_View::factory(
    'SavedQueries',
    array('title' => $GLOBALS['registry']->getAuth() ? _("My Queries") : _("Public Queries"),
          'results' => $qManager->listQueries($GLOBALS['registry']->getAuth(), true)));
$myqueries->html();

require $registry->get('templates', 'horde') . '/common-footer.inc';
