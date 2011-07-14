<?php
/**
 * Uppercases action drivers in a backend.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Vilius Šumskas <vilius@lnk.lt>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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
              $values = array(ucfirst($form['form_action']),
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