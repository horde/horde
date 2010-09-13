<?php
/**
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$jonah = Horde_Registry::appInit('jonah', array(
    'authentication' => 'none',
    'session_control' => 'readonly'
));

$m = new Horde_Routes_Mapper();

require JONAH_BASE . '/config/routes.php';
require JONAH_BASE . '/config/templates.php';

// Grab, and hopefully match, the URL
$request = new Horde_Controller_Request_Http();
$url = $request->getPath();

$args = $request->getGetParams();
$result = $m->match('/' . $url);

$criteria = array();
// @TODO: This should be handled by controller objects, but for now just use
// a switch conditional until we move to Horde_Controller
switch ($result['controller']) {
case 'admin':
    // TODO:
    exit;
case 'feed':
    // Default settings
    $defaults = array(
        'format' => 'html',
        'feed' => $result['feed'],
    );

    // Check for the format specification
    if ($pos = strrpos($result['feed'], '.')) {
        $criteria['feed'] = substr($result['feed'], 0, $pos);
        $criteria['format'] = substr($result['feed'], $pos + 1);
    }

    if (!empty($result['filter'])) {
        switch ($result['filter']) {
        case 'author':
            $criteria['author'] = $result['value'];
            break;

        case 'date':
            if (preg_match('/\d{4}-\d{1,2}/', $result['value'])) {
                list($year, $month) = explode('-', $result['value']);
                $criteria['updated-min'] = new Horde_Date(array(
                                                   'month' => $month,
                                                   'year' => $year));
                // Set the end date to the end of the requested month
                $criteria['updated-max'] = new Horde_Date(array(
                                                   'month' => ++$month,
                                                   'year' => $year));
                $criteria['updated-max']->sec--;
                $criteria['updated-max']->correct();
                break;
            }

        // @TODO: These will be implemented as GData's categories, not as
        // part of the route proper.
        case 'tag':
            $criteria['tags'] = array($result['value']);
            break;
        }
    }

    if (isset($args['tags'])) {
        if (strpos($args['tags'], '|') !== false) {
            // We have an OR list of tags
            $criteria['tags'] = explode('|', $args['tags']);
        } elseif (strpos($args['tags'], ',') !== false) {
            // We have an AND list of tags
            $criteria['alltags'] = explode(',', $args['tags']);
        } else {
            // Just a single tag
            $criteria['tags'] = array($args['tags']);
        }
    }
    unset($args['tags']);

    // These dates are expected to be in RFC 3339 format
    if (isset($args['updated-min'])) {
        $criteria['updated-min'] = new Horde_Date($args['updated-min']);
        unset($args['updated-min']);
    }
    if (isset($args['updated-max'])) {
        $criteria['updated-max'] = new Horde_Date($args['updated-max']);
        unset($args['updated-max']);
    }
    if (isset($args['published-min'])) {
        $criteria['published-min'] = new Horde_Date($args['published-min']);
        unset($args['published-min']);
    }
    if (isset($args['published-max'])) {
        $criteria['published-max'] = new Horde_Date($args['published-max']);
        unset($args['published-max']);
    }

    // Parse keyword search arguments
    $keywords = array();
    $notkeywords = array();
    if (isset($args['q'])) {
        $query = $args['q'];

        // Look for quoted strings
        while (($quotepos = strpos($query, '"')) !== false) {
            if ($quotepos !== 0) {
                $keywords = array_merge(explode(' ', substr($query, 0, $quotepos)), $keywords);
                $query = substr($query, $quotepos);
            }

            $keywords[] = substr($query, 1, strpos($query, '"', 1) - 1);
            $query = substr($query, 1);
            $query = substr($query, strpos($query, '"', 1) + 1);
        }

        // Split up any remaining text into keywords
        $keywords = array_merge(explode(' ', $query), $keywords);

        // Remove duplicates and empty values
        $keywords = array_flip($keywords);
        unset ($keywords['']);
        $keywords = array_flip($keywords);

        // We're done with 'q'.  Unset it to prevent it being copied into
        // $criteria below.
        unset($args['q']);

        foreach ($keywords as $index => $keyword) {
            if (substr($keyword, 0, 1) == '-') {
                $notkeywords[] = substr($keyword, 1);
                unset($keywords[$index]);
            }
        }

        // Save the criteria
        if (!empty($keywords)) {
            $criteria['keywords'] = $keywords;
        }
        if (!empty($notkeywords)) {
            $criteria['notkeywords'] = $notkeywords;
        }
    }

    // Preserve remaining args
    // @TODO: Don't think we need to preserve the query string once we get here.
    $criteria = array_merge($defaults, $args, $criteria);
    $class = 'Jonah_View_Delivery' . $criteria['format'];

    //@TODO: FIXME - format (html/rss/pdf) is dealt with by the view object we
    // instantiate but html _currently_ needs a format. Think we'll just have to
    // pick a default format to render when requested this way.
    $criteria['format'] = 'standard';
    $params = array('registry' => &$registry,
                    'notification' => &$notification,
                    'conf' => &$conf,
                    'criteria' => &$criteria);
    $view = new $class($params);
    $view->run();
    break;
}
