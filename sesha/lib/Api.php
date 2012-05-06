<?php
/**
 * Sesha external API interface.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * This file defines Sesha's external API interface. Other applications can
 * interact with Sesha through this API.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => 'stringArray');

$_services['listQueues'] = array(
    'args' => array(),
    'type' => '{urn:horde}hash'
);

$_services['getQueueDetails'] = array(
    'args' => array('queue_id' => 'int'),
    'type' => '{urn:horde}hash'
);

$_services['listVersions'] = array(
    'args' => array('queue_id' => 'int'),
    'type' => '{urn:horde}hashHash'
);

$_services['getVersionDetails'] = array(
    'args' => array('version_id' => 'int'),
    'type' => '{urn:horde}hash'
);


function _sesha_perms()
{
    $perms = array();
    $perms['tree']['sesha']['admin'] = array();
    $perms['title']['sesha:admin'] = _("Administration");
    $perms['tree']['sesha']['addStock'] = array();
    $perms['title']['sesha:addStock'] = _("Add Stock");

    return $perms;
}

function _sesha_listQueues()
{
    require_once dirname(__FILE__) . '/base.php';

    $queues = array();
    $categories = $GLOBALS['backend']->getCategories();
    foreach ($categories as $category) {
        $queues[$category['category_id']] = $category['category'];
    }
    asort($queues);

    return $queues;
}

/**
 * Get a queueDetails hash for a queue (category)
 * @param integer $queue_id  The Queue for which to build the details hash
 * @return array  A hash of category id as id, category label as name, category description as description, a link, a list of subjects as configured
 */
function _sesha_getQueueDetails($queue_id)
{
    global $registry;
    $category = $GLOBALS['backend']->getCategory($queue_id);

    return array('id' => $queue_id,
                 'name' => $category->category,
                 'description' => $category->description,
                 'link' => Horde_Util::addParameter(Horde::applicationUrl('list.php', true), 'display_category', $queue_id - 1, false),
                 'subjectlist' => $GLOBALS['conf']['tickets']['subjects'],
                 'versioned' => $registry->hasMethod('tickets/listVersions') == $registry->getApp(),
                 'readonly' => true);
}

/**
 * List Stock items as versions for a queue (category)
 * @param integer $queue_id  The category id (queue) for which we want to fetch versions
 * @return array  A hash containing stock id as id, stock name as name, stock note as description
 */
function _sesha_listVersions($queue_id)
{
    $inventory = $GLOBALS['backend']->findStock(array('categories' => $queue_id));
    $versions = array();
    foreach ($inventory as $item) {
        $versions[] = array('id' => $item->stock_id,
                            'name' => $item->stock_name,
                            'description' => $item->note,
                            'readonly' => true);
    }
    Horde_Array::arraySort($versions, 'name', 0, false);

    return $versions;
}

/**
 * return a version details hash by version id
 * @param integer $version_id  The ID of the stock item to display as a version
 * @return array  The version hash containing stock name as name, stock note as description and a link
 */
function _sesha_getVersionDetails($version_id)
{
    $item = $GLOBALS['backend']->fetch($version_id);
    return array('id' => $version_id,
                 'name' => $item->stock_name,
                 'description' => $item->note,
                 'link' => Horde_Util::addParameter(Horde::applicationUrl('stock.php', true), array('stock_id' => $version_id, 'actionId' => 'view_stock'), null, false),
                 'readonly' => true);
}
