<?php
/**
 * Move tags from Kronolith to content storage. This migration ONLY migrates
 * categories from the Horde_Share_Sql backend.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package Kronolith
 */
class KronolithUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    public function __construct(Horde_Db_Adapter $connection, $version = null)
    {
        parent::__construct($connection, $version);

        // Can't use Kronolith's tagger since we can't init kronolith.
        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('calendar', 'event'));
        $this->_type_ids = array('calendar' => (int)$types[0], 'event' => (int)$types[1]);
        $this->_tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        $this->_shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('kronolith');
    }

    public function up()
    {
        $sql = 'SELECT event_uid, event_category, event_creator_id, calendar_id FROM kronolith_events';
        $this->announce('Migrating event categories to tags.');
        $rows = $this->select($sql);
        foreach ($rows as $row) {
            $this->_tagger->tag(
                $row['event_creator_id'],
                array('object' => (string)$row['event_uid'], 'type' => $this->_type_ids['event']),
                $row['event_category']
            );

            // Do we need to tag the event again, but as the share owner?
            try {
                $cal = $this->_shares->getShare($row['calendar_id']);
                if ($cal->get('owner') != $row['event_creator_id']) {
                    $this->_tagger->tag(
                        $cal->get('owner'),
                        array('object' => (string)$row['event_uid'], 'type' => $this->_type_ids['event']),
                        $row['event_category']
                    );
                }
            } catch (Exception $e) {
                $this->announce('Unable to find Share: ' . $row['calendar_id'] . ' Skipping.');
            }
        }
        $this->announce('Event categories successfully migrated.');
        $this->removeColumn('kronolith_events', 'event_category');
    }

    public function down()
    {
        $this->addColumn('kronolith_events', 'event_category', 'string', array('limit' => 80));
        $this->announce('Migrating event tags to categories.');
        $sql = 'UPDATE kronolith_events SET event_category = ? WHERE event_uid = ?';
        $rows = $this->select('SELECT event_uid, event_category, event_creator_id, calendar_id FROM kronolith_events');
        foreach ($rows as $row) {
            $tags = $this->_tagger->getTagsByObjects($row['event_uid'], $this->_type_ids['event']);
            if (!count($tags) || !count($tags[$row['event_uid']])) {
                continue;
            }
            $this->update($sql, array(reset($tags[$row['event_uid']]), (string)$row['event_uid']));
        }
        $this->announce('Event tags successfully migrated.');
    }
}