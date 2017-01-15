<?php
/**
 * Copyright 2013-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

/**
 * Replaces the unique index for external object IDs with a regular index.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class HordeDavRemoveUniqueIndex extends Horde_Db_Migration_Base
{
    /**
     * Upgrade
     */
    public function up()
    {
        $this->removeIndex('horde_dav_objects', 'id_external');
        $this->addIndex('horde_dav_objects', 'id_external');
        $this->addIndex('horde_dav_objects', array('id_external', 'id_collection'), array('unique' => true));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $indexes = $this->indexes('horde_dav_objects');
        $idx_names = array(
            $this->indexName(
                'horde_dav_objects',
                array('column' => array('id_external', 'id_collection'))
            ),
            $this->indexName('horde_dav_objects', 'id_external')
        );
        foreach ($indexes as $idx) {
            if (in_array($idx->name, $idx_names)) {
                $this->removeIndex('horde_dav_objects', array('name' => $idx->name));
            }
        }
        $this->addIndex('horde_dav_objects', 'id_external', array('unique' => true));
    }
}
