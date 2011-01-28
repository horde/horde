<?php
/**
 * Drops domain keys and converts user_enabled to boolean.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Vilma
 */
class VilmaDropDomainkey extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->removeColumn('vilma_domains', 'domain_key');
        $this->changeColumn('vilma_users', 'user_enabled', 'boolean', array('default' => true, 'null' => false));
    }

    /**
     * Downgrade to 0
     */
    public function down()
    {
        $this->changeColumn('vilma_users', 'user_enabled', 'integer', array('default' => 1, 'null' => false));
        $this->addColumn('vilma_domains', 'domain_key', 'string', array('limit' => 64));
    }
}
