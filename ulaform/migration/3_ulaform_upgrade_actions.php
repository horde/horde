<?php
/**
 * Uppercases action drivers in a backend.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Vilius Šumskas <vilius@lnk.lt>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ulaform
 */
class UlaformUpgradeActions extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $sql = 'UPDATE ulaform_forms SET form_action = ? WHERE form_id = ?';
        foreach ($this->select('SELECT form_id, form_action FROM ulaform_forms') as $form) {
            $values = array(Horde_String::ucfirst($form['form_action']),
                            $form['form_id']);
            $this->execute($sql, $values);
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        $this->execute('UPDATE ulaform_forms SET form_action = LOWER(form_action)');
    }
}