<?php
/**
 * Adds autoincrement flags
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Vilius Šumskas <vilius@lnk.lt>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ulaform
 */
class UlaformUpgradeAutoIncrement extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('ulaform_fields', 'field_id', 'autoincrementKey');
        try {
            $this->dropTable('ulaform_fields_seq');
        } catch (Horde_Db_Exception $e) {}

        $this->changeColumn('ulaform_forms', 'form_id', 'autoincrementKey');
        try {
            $this->dropTable('ulaform_forms_seq');
        } catch (Horde_Db_Exception $e) {}
    }

    /**
     * Downgrade
     */
    public function down()
    {
        $this->changeColumn('ulaform_fields', 'field_id', 'integer', array('autoincrement' => false));
        $this->changeColumn('ulaform_forms', 'form_id', 'integer', array('autoincrement' => false));
    }

}
