<?php
/**
 * Create Vilma base tables as of 2010-12-13.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Vilma
 */
class VilmaBaseTables extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $tableList = $this->tables();

        if (!in_array('vilma_domains', $tableList)) {
            $t = $this->createTable('vilma_domains', array('primaryKey' => 'domain_id'));
            $t->column('domain_id', 'integer', array('null' => false));
            $t->column('domain_name', 'string', array('limit' => 128, 'null' => false));
            $t->column('domain_transport', 'string', array('limit' => 128, 'null' => false));
            $t->column('domain_max_users', 'integer', array('default' => 0, 'null' => false));
            $t->column('domain_quota', 'integer', array('default' => 0, 'null' => false));
            $t->column('domain_key', 'string', array('limit' => 64));
            $t->end();

            $this->addIndex('vilma_domains', 'domain_name', array('unique' => true));
        }

        if (!in_array('vilma_users', $tableList)) {
            $t = $this->createTable('vilma_users', array('primaryKey' => 'user_id'));
            $t->column('user_id', 'integer', array('null' => false));
            $t->column('user_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_clear', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_crypt', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_full_name', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_uid', 'integer', array('null' => false));
            $t->column('user_gid', 'integer', array('null' => false));
            $t->column('user_home_dir', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_mail_dir', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_mail_quota', 'integer', array('default' => 0, 'null' => false));
            $t->column('user_ftp_dir', 'string', array('limit' => 255, 'null' => false));
            $t->column('user_ftp_quota', 'integer', array('default' => 0, 'null' => false));
            $t->column('user_enabled', 'integer', array('default' => 1, 'null' => false));
            $t->end();

            $this->addIndex('vilma_users', 'user_name', array('unique' => true));
        }

        if (!in_array('vilma_virtuals', $tableList)) {
            $t = $this->createTable('vilma_virtuals', array('primaryKey' => 'virtual_id'));
            $t->column('virtual_id', 'integer', array('null' => false));
            $t->column('virtual_email', 'string', array('limit' => 128, 'null' => false));
            $t->column('virtual_destination', 'string', array('limit' => 128, 'null' => false));
            $t->end();
        }
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->dropTable('vilma_domains');
        $this->dropTable('vilma_users');
        $this->dropTable('vilma_virtuals');
    }
}
