<?php
/**
 * Renames the default pages to move them into the Wiki/ namespace.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Wicked
 */
class WickedRenamePages extends Horde_Db_Migration_Base
{
    protected $_pages = array('AddingPages'    => 'Wiki/AddingPages',
                              'HowToUseWiki'   => 'Wiki/Usage',
                              'SandBox'        => 'Wiki/SandBox',
                              'WikiHome'       => 'Wiki/Home',
                              'WikiPage'       => 'Wiki/Page',
                              'WickedTextFormat' => 'Wiki/TextFormat');

    /**
     * Upgrade.
     */
    public function up()
    {
        foreach ($this->_pages as $old => $new) {
            $exists = $this->selectValue(
                'SELECT 1 FROM wicked_pages WHERE page_name = ?', array($new));
            if ($exists) {
                continue;
            }
            try {
                $this->beginDbTransaction();
                $this->update('UPDATE wicked_pages SET page_name = ? WHERE page_name = ?', array($new, $old));
                $this->update('UPDATE wicked_history SET page_name = ? WHERE page_name = ?', array($new, $old));
                $this->commitDbTransaction();
            } catch (Horde_Db_Exception $e) {
                $this->rollbackDbTransaction();
            }
        }
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        foreach ($this->_pages as $old => $new) {
            $exists = $this->selectValue(
                'SELECT 1 FROM wicked_pages WHERE page_name = ?', array($old));
            if ($exists) {
                continue;
            }
            try {
                $this->beginDbTransaction();
                $this->update('UPDATE wicked_pages SET page_name = ? WHERE page_name = ?', array($old, $new));
                $this->update('UPDATE wicked_history SET page_name = ? WHERE page_name = ?', array($old, $new));
                $this->commitDbTransaction();
            } catch (Horde_Db_Exception $e) {
                $this->rollbackDbTransaction();
            }
        }
    }
}
