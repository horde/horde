<?php
/**
 * Sesha external API interface.
 *
 * Copyright 2003-2011 Horde LLC (http://www.horde.org/)
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
    * List all available categories as queues
    * @return array
    */
    public function listQueues()
    {
        $queues = array();
        $categories = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create()->getCategories();
        foreach ($categories as $category) {
            $queues[$category['category_id']] = $category['category'];
        }
        asort($queues);

        return $queues;
    }

   /**
    * Retrieve queue details - this is for ticketing integration
    *
    * @param string $queue_id The id of the queue to retrieve
    *
    * @return array
    */
    public function getQueueDetails($queue_id)
    {
        global $registry;

        $category = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create()->getCategory($queue_id);

        return array('id' => $queue_id,
                 'name' => $category['category'],
                 'description' => $category['description'],
                 'link' => Horde_Util::addParameter(Horde::url('list.php', true), 'display_category', $queue_id - 1, false),
                 'subjectlist' => $GLOBALS['conf']['tickets']['subjects'],
                 'versioned' => $registry->hasMethod('tickets/listVersions') == $registry->getApp(),
                 'readonly' => true);
    }

   /**
    * List all versions for the queue
    *
    * @param string $queue_id The id of the queue
    *
    * @return array $versions List of versions
    */
    public function listVersions($queue_id)
    {
        $inventory = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create()->listStock($queue_id);
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

   /**
    * Retrieve item version details 
    *
    * @param string $version_id The id of the version to retrieve
    *
    * @throws Sesha_Exception
    * @return array
    */
    public function getVersionDetails($version_id)
    {
        global $registry;

        try {
            $item = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create()->fetch($version_id);
        } catch (Sesha_Exception $e) {
            return array();
        }
        return array('id' => $version_id,
                 'name' => $item['stock_name'],
                 'description' => $item['note'],
                 'link' => Horde_Util::addParameter(Horde::url('stock.php', true), array('stock_id' => $version_id, 'actionId' => 'view_stock'), null, false),
                 'readonly' => true);
    }
}
