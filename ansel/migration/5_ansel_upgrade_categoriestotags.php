<?php
/**
 * Move tags from ansel to content storage.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class AnselUpgradeCategoriesToTags extends Horde_Db_Migration_Base
{
    public function up()
    {
        $GLOBALS['registry']->pushApp('ansel');

        /* Gallery tags */
        $t = $this->_connection->table('ansel_shares');
        $cols = $t->getColumns();
        if (in_array('attribute_category', array_keys($cols))) {
            $sql = 'SELECT share_id, attribute_category, share_owner FROM ansel_shares';
            $this->announce('Migrating gallery categories.');
            $rows = $this->_connection->selectAll($sql);
            foreach ($rows as $row) {
                $GLOBALS['injector']->getInstance('Ansel_Tagger')->tag($row['share_id'], $row['attribute_category'], $row['share_owner'], 'gallery');
            }
            $this->announce('Gallery categories successfully migrated.');
            $this->removeColumn('ansel_shares', 'attribute_category');
        } else {
            $this->announce('Gallery categories ALREADY migrated.');
        }
    }

    public function down()
    {
        // Not supported, no way to tell which tags were categories.
    }

}