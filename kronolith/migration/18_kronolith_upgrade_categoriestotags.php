<?php
/**
 * Move tags from Kronolith to content storage. This migration ONLY migrates
 * categories from the Horde_Share_Sql backend.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package Kronolith
 */
class KronolithUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    public function up()
    {
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();

        // Can't use Kronolith's tagger since we can't init kronolith.
        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the Content application is installed.');
        }
        $type_mgr = $GLOBALS['injector']->getInstance('Content_Types_Manager');
        $types = $type_mgr->ensureTypes(array('calendar', 'event'));
        $type_ids = array('calendar' => (int)$types[0], 'event' => (int)$types[1]);
        $tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('kronolith');

        if (in_array('event_category', array_keys($cols))) {
            $sql = 'SELECT event_uid, event_category, event_creator_id, calendar_id FROM kronolith_events';
            $this->announce('Migrating event categories.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {

                $tagger ->tag(
                    $row['event_creator_id'],
                    array('object' => $row['event_uid'], 'type' => $type_ids['event']),
                    Horde_String::convertCharset($row['event_category'], $this->_connection->getOption('charset'), 'UTF-8')
                );

                // Do we need to tag the event again, but as the share owner?
                try {
                    $cal = $shares->getShare($row['calendar_id']);
                } catch (Exception $e) {
                    $this->announce('Unable to find Share: ' . $row['calendar_id'] . ' Skipping.');
                }

                if ($cal->get('owner') != $row['event_creator_id']) {
                    $tagger ->tag(
                        $cal->get('owner'),
                        array('object' => $row['event_uid'], 'type' => $type_ids['event']),
                        Horde_String::convertCharset($row['event_category'], $this->_connection->getOption('charset'), 'UTF-8')
                    );
                }
            }
            $this->announce('Event categories successfully migrated.');
            $this->removeColumn('kronolith_events', 'event_category');
        } else {
            $this->announce('Event categories ALREADY migrated or unsupported driver.');
        }
    }

    public function down()
    {
        // This is a one-way data migration. No way to know which tags were
        // from categories. Just put back the column.
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('event_category', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'event_category', 'string', array('limit' => 80));
        }
    }

}