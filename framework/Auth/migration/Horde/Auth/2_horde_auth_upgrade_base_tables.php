<?php
/**
 * Adds lock_field field
 * Adds lock_expiration_field field
 * Adds bad_login_count_field field
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * If you did not receive this file, see 
 * http://www.horde.org/licenses/lgpl
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL
 * @package  Auth
 */
class HordeAuthUpgradeBaseTables extends Horde_Db_Migration_Base
{

    /**
     * Upgrade.
     */
    public function up()
    {
        $t = $this->_connection->table('horde_users');
        $cols = $t->getColumns();
        if (!in_array('lock_field', array_keys($cols))) {
            $this->addColumn('horde_users', 'lock_field', 'boolean', array('default' => false));
        }
        if (!in_array('lock_expiration_field', array_keys($cols))) {
            $this->addColumn('horde_users', 'lock_expiration_field', 'integer', array('default' => '0', 'null' => false));
        }
        if (!in_array('bad_login_count_field', array_keys($cols))) {
            $this->addColumn('horde_users', 'bad_login_count_field', 'integer', array('default' => '0', 'null' => false));
        }
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('horde_users', 'lock_field');
        $this->removeColumn('horde_users', 'lock_expiration_field');
        $this->removeColumn('horde_users', 'bad_login_count_field');
    }

}
