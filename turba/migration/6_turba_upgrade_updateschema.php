<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Turba
 */

require_once dirname(__FILE__) . '/../lib/Turba.php';

/**
 * Add hierarchcal related columns to the legacy sql share driver
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Turba
 */
class TurbaUpgradeUpdateSchema extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->addColumn('turba_objects', 'object_anniversary', 'string', array('limit' => 10));
        $this->addColumn('turba_objects', 'object_department', 'string', array('limit' => 255));
        $this->addColumn('turba_objects', 'object_spouse', 'string', array('limit' => 255));
        $this->addColumn('turba_objects', 'object_homefax', 'string', array('limit' => 25));
        $this->addColumn('turba_objects', 'object_anniversary', 'string', array('limit' => 10));
        $this->addColumn('turba_objects', 'object_nickname', 'string', array('limit' => 255));
        $this->addColumn('turba_objects', 'object_assistantphone', 'string', array('limit' => 25));
        $this->addColumn('turba_objects', 'object_imaddress', 'string', array('limit' => 255));
        $this->addColumn('turba_objects', 'object_imaddress2', 'string', array('limit' => 255));
        $this->addColumn('turba_objects', 'object_imaddress3', 'string', array('limit' => 255));
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->removeColumn('turba_objects', 'object_anniversary');
        $this->removeColumn('turba_objects', 'object_department');
        $this->removeColumn('turba_objects', 'object_spouse');
        $this->removeColumn('turba_objects', 'object_homefax');
        $this->removeColumn('turba_objects', 'object_anniversary');
        $this->removeColumn('turba_objects', 'object_nickname');
        $this->removeColumn('turba_objects', 'object_assistantphone');
        $this->removeColumn('turba_objects', 'object_imaddress');
        $this->removeColumn('turba_objects', 'object_imaddress3');
    }

}
