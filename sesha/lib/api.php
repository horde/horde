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

function _sesha_getQueueDetails($queue_id)
{
    global $registry;

    require_once dirname(__FILE__) . '/base.php';

    $category = $GLOBALS['backend']->getCategory($queue_id);

    return array('id' => $queue_id,
                 'name' => $category['category'],
                 'description' => $category['description'],
                 'link' => Horde_Util::addParameter(Horde::applicationUrl('list.php', true), 'display_category', $queue_id - 1, false),
                 'subjectlist' => $GLOBALS['conf']['tickets']['subjects'],
                 'versioned' => $registry->hasMethod('tickets/listVersions') == $registry->getApp(),
                 'readonly' => true);
}

function _sesha_listVersions($queue_id)
{
    require_once dirname(__FILE__) . '/base.php';
    require_once 'Horde/Array.php';

    $inventory = $GLOBALS['backend']->listStock($queue_id);
    $versions = array();
    foreach ($inventory as $item) {
        $versions[] = array('id' => $item['stock_id'],
                            'name' => $item['stock_name'],
                            'description' => $item['note'],
                            'readonly' => true);
    }
    Horde_Array::arraySort($versions, 'name', 0, false);

    return $versions;
}

function _sesha_getVersionDetails($version_id)
{
    global $registry;

    require_once dirname(__FILE__) . '/base.php';

    $item = $GLOBALS['backend']->fetch($version_id);
    if (is_a($item, 'PEAR_Error')) {
        return $item;
    }

    return array('id' => $version_id,
                 'name' => $item['stock_name'],
                 'description' => $item['note'],
                 'link' => Horde_Util::addParameter(Horde::applicationUrl('stock.php', true), array('stock_id' => $version_id, 'actionId' => 'view_stock'), null, false),
                 'readonly' => true);
}
