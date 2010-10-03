<?php
/**
 * Upgrade to Ansel 2 style schema
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
class AnselUpgradeStyle extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('ansel_shares', 'attribute_style', 'text');
        
        // Create: ansel_hashes
        $t = $this->createTable('ansel_hashes', array('primaryKey' => 'style_hash'));
        $t->column('style_hash', 'string', array('limit' => 255));
        $t->end();
    }

    public function down()
    {
        $this->changeColumn('ansel_shares', 'attribute_style', 'string',  array('limit' => 255));
        $this->dropTable('ansel_hashes');
    }

}