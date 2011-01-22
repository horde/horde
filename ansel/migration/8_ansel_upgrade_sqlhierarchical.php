<?php
/**
 * Migrate to Horde_Share_Sql hierarchical shares.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class AnselUpgradeSqlHierarchical extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->removeIndex('ansel_shares', 'share_parents');
        $this->addColumn('ansel_shares', 'share_name', 'string', array('limit' => 255, 'null' => false));
        $this->changeColumn('ansel_shares', 'share_parents', 'text');

        // Add sharenames
        $sql = 'SELECT share_id FROM ansel_shares;';
        $ids = $this->_connection->selectValues($sql);
        $sql = 'UPDATE ansel_shares SET share_name = ? WHERE share_id = ?';
        foreach ($ids as $id) {
            $params = array(strval(new Horde_Support_Randomid()), $id);
            $this->_connection->update($sql, $params);
        }
    }

    /**
     * Downgrade
     *
     */
    public function down()
    {
        $this->removeColumn('ansel_shares', 'share_name');
        $this->changeColumn('ansel_shares', 'share_parents', 'string', array('limit' => 255));
        $this->addIndex('ansel_shares', array('share_parents'));
    }

}
