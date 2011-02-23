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
        $GLOBALS['registry']->pushApp('kronolith');

        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (in_array('event_category', array_keys($cols))) {
            $sql = 'SELECT event_uid, event_category, event_creator_id, calendar_id FROM kronolith_events';
            $this->announce('Migrating event categories.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $GLOBALS['injector']
                    ->getInstance('Kronolith_Tagger')
                    ->tag($row['event_uid'], $row['event_category'], $row['event_creator_id']);

                // Do we need to tag the event again, but as the share owner?
                try {
                    $cal = $GLOBALS['kronolith_shares']->getShare($row['calendar_id']);
                } catch (Exception $e) {
                    $this->announce('Unable to find Share: ' . $row['calendar_id'] . ' Skipping.');
                }

                if ($cal->get('owner') != $row['event_creator_id']) {
                    $GLOBALS['injector']
                        ->getInstance('Kronolith_Tagger')
                        ->tag($row['event_uid'], $row['event_category'], $cal->get('owner'));
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
        $GLOBALS['registry']->pushApp('kronolith');
        $t = $this->_connection->table('kronolith_events');
        $cols = $t->getColumns();
        if (!in_array('event_category', array_keys($cols))) {
            $this->addColumn('kronolith_events', 'event_category', 'string', array('limit' => 80));
        }
    }

}