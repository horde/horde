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
class Sesha_Api extends Horde_Registry_Api
{

    /**
     * List categories as ticket queues
     * @return array  a list of ticket queues with category id as key and category caption as value
     */
    public function listQueues()
    {
        $queues = array();
        $categories = $GLOBALS['backend']->getCategories();
        foreach ($categories as $category) {
            $queues[$category->category_id] = $category->category;
        }
        asort($queues);

        return $queues;
    }

    /**
     * Get a queueDetails hash for a queue (category)
     * @param integer $queue_id  The Queue for which to build the details hash
     * @return array  A hash of category id as id, category label as name, category description as description, a link, a list of subjects as configured
     */
    public function getQueueDetails($queue_id)
    {
        global $registry;
        $category = $GLOBALS['backend']->getCategory($queue_id);

        return array('id' => $queue_id,
                    'name' => $category->category,
                    'description' => $category->description,
                    'link' => Horde::applicationUrl('list.php', true)->add('display_category', $queue_id - 1)->setRaw(true),
                    'subjectlist' => $GLOBALS['conf']['tickets']['subjects'],
                    'versioned' => $registry->hasMethod('tickets/listVersions') == $registry->getApp(),
                    'readonly' => true);
    }

    /**
     * List Stock items as versions for a queue (category)
     * @param integer $queue_id  The category id (queue) for which we want to fetch versions
     * @return array  A hash containing stock id as id, stock name as name, stock note as description
     */
    public function listVersions($queue_id)
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
    public function getVersionDetails($version_id)
    {
        $item = $GLOBALS['backend']->fetch($version_id);
        return array('id' => $version_id,
                    'name' => $item->stock_name,
                    'description' => $item->note,
                    'link' => Horde::applicationUrl('stock.php', true)->add(array('stock_id' => $version_id, 'actionId' => 'view_stock'))->setRaw(true),
                    'readonly' => true);
    }
}
