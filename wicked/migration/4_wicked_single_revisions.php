<?php
/**
 * Changes major.minor revisions to single revision numbers.
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
class WickedSingleRevisions extends Horde_Db_Migration_Base
{
    private $_vfs;

    public function __construct(Horde_Db_Adapter $connection, $version = null)
    {
        parent::__construct($connection, $version);
        require_once $GLOBALS['registry']->get('fileroot', 'wicked') . '/lib/Wicked.php';
        $this->_vfs = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_Vfs')
            ->create();
    }

    /**
     * Upgrade.
     */
    public function up()
    {
        /* Pages */
        $this->removePrimaryKey('wicked_history');
        $this->addColumn('wicked_pages', 'page_version', 'integer', array('null' => false, 'default' => 0));
        $this->addColumn('wicked_history', 'page_version', 'integer', array('null' => false, 'default' => 0));

        $id = $version = null;
        $query = 'UPDATE wicked_history SET page_version = ? WHERE '
            . 'page_id = ? AND page_majorversion = ? AND page_minorversion = ?';
        $pageQuery = 'UPDATE wicked_pages SET page_version = ? WHERE page_id = ?';
        $history = $this->select(
            'SELECT page_id, page_majorversion, page_minorversion '
            . 'FROM wicked_history '
            . 'ORDER BY page_id, page_majorversion, page_minorversion');
        foreach ($history as $entry) {
            // Next page? Reset version.
            if ($entry['page_id'] != $id) {
                $version = 1;
            }
            $this->update($query,
                          array($version,
                                $entry['page_id'],
                                $entry['page_majorversion'],
                                $entry['page_minorversion']));
            $this->update($pageQuery, array($version + 1, $entry['page_id']));
            $id = $entry['page_id'];
            $version++;
        }
        $this->update('UPDATE wicked_pages SET page_version = 1 WHERE page_version = 0');

        $this->addPrimaryKey('wicked_history', array('page_id', 'page_version'));
        $this->removeColumn('wicked_pages', 'page_majorversion');
        $this->removeColumn('wicked_pages', 'page_minorversion');
        $this->removeColumn('wicked_history', 'page_majorversion');
        $this->removeColumn('wicked_history', 'page_minorversion');

        /* Attachments */
        $this->removePrimaryKey('wicked_attachment_history');
        $this->addColumn('wicked_attachments', 'attachment_version', 'integer', array('null' => false, 'default' => 0));
        $this->addColumn('wicked_attachment_history', 'attachment_version', 'integer', array('null' => false, 'default' => 0));

        $id = $name = $version = null;
        $query = 'UPDATE wicked_attachment_history SET attachment_version = ? '
            . 'WHERE page_id = ? AND attachment_name = ? '
            . 'AND attachment_majorversion = ? AND attachment_minorversion = ?';
        $pageQuery = 'UPDATE wicked_attachments SET attachment_version = ? '
            . 'WHERE page_id = ? AND attachment_name = ?';
        $history = $this->select(
            'SELECT page_id, attachment_name, attachment_majorversion, '
            . 'attachment_minorversion FROM wicked_attachment_history '
            . 'ORDER BY page_id, attachment_name, attachment_majorversion, '
            . 'attachment_minorversion');
        foreach ($history as $entry) {
            // Next page? Reset version.
            if ($entry['page_id'] != $id ||
                $entry['attachment_name'] != $name) {
                $version = 1;
            }
            $this->_rename(
                $entry['page_id'],
                $entry['attachment_name'],
                $entry['attachment_majorversion'] . '.' . $entry['attachment_minorversion'],
                $version);
            $this->update($query,
                          array($version,
                                $entry['page_id'],
                                $entry['attachment_name'],
                                $entry['attachment_majorversion'],
                                $entry['attachment_minorversion']));
            $this->update($pageQuery,
                          array($version + 1,
                                $entry['page_id'],
                                $entry['attachment_name']));
            $id = $entry['page_id'];
            $name = $entry['attachment_name'];
            $version++;
        }

        $query = $this->addLimitOffset(
            'SELECT attachment_version FROM wicked_attachment_history '
            . 'WHERE page_id = ? AND attachment_name = ? '
            . 'ORDER BY attachment_version DESC',
            array('limit' => 1));
        $attachments = $this->select(
            'SELECT page_id, attachment_name, attachment_majorversion, '
            . 'attachment_minorversion FROM wicked_attachments');
        foreach ($attachments as $attachment) {
            $version = $this->selectValue(
                $query,
                array($attachment['page_id'], $attachment['attachment_name']));
            if ($version === false) {
                $version = 1;
            } else {
                $version++;
            }
            $this->_rename(
                $attachment['page_id'],
                $attachment['attachment_name'],
                $attachment['attachment_majorversion'] . '.' . $attachment['attachment_minorversion'],
                $version);
        }

        $this->update('UPDATE wicked_attachments SET attachment_version = 1 WHERE attachment_version = 0');

        $this->addPrimaryKey(
            'wicked_attachment_history',
            array('page_id', 'attachment_name', 'attachment_version'));
        $this->removeColumn('wicked_attachments', 'attachment_majorversion');
        $this->removeColumn('wicked_attachments', 'attachment_minorversion');
        $this->removeColumn('wicked_attachment_history', 'attachment_majorversion');
        $this->removeColumn('wicked_attachment_history', 'attachment_minorversion');
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        /* Pages */
        $this->addColumn('wicked_pages', 'page_majorversion', 'integer', array('null' => false));
        $this->addColumn('wicked_pages', 'page_minorversion', 'integer', array('null' => false));
        $this->update('UPDATE wicked_pages SET page_majorversion = page_version, page_minorversion = 0');
        $this->removeColumn('wicked_pages', 'page_version');

        $this->removePrimaryKey('wicked_history');
        $this->addColumn('wicked_history', 'page_majorversion', 'integer', array('null' => false));
        $this->addColumn('wicked_history', 'page_minorversion', 'integer', array('null' => false));
        $this->update('UPDATE wicked_history SET page_majorversion = page_version, page_minorversion = 0');
        $this->removeColumn('wicked_history', 'page_version');
        $this->addPrimaryKey('wicked_history', array('page_id', 'page_majorversion', 'page_minorversion'));

        /* Attachments */
        $attachments = $this->select(
            'SELECT page_id, attachment_name, attachment_version '
            . 'FROM wicked_attachments');
        foreach ($attachments as $attachment) {
            $this->_rename(
                $attachment['page_id'],
                $attachment['attachment_name'],
                $attachment['attachment_version'],
                $attachment['attachment_version'] . '.0');
        }
        $attachments = $this->select(
            'SELECT page_id, attachment_name, attachment_version '
            . 'FROM wicked_attachment_history');
        foreach ($attachments as $attachment) {
            $this->_rename(
                $attachment['page_id'],
                $attachment['attachment_name'],
                $attachment['attachment_version'],
                $attachment['attachment_version'] . '.0');
        }

        $this->addColumn('wicked_attachments', 'attachment_majorversion', 'integer', array('null' => false));
        $this->addColumn('wicked_attachments', 'attachment_minorversion', 'integer', array('null' => false));
        $this->update('UPDATE wicked_attachments SET attachment_majorversion = attachment_version, attachment_minorversion = 0');
        $this->removeColumn('wicked_attachments', 'attachment_version');

        $this->removePrimaryKey('wicked_attachment_history');
        $this->addColumn('wicked_attachment_history', 'attachment_majorversion', 'integer', array('null' => false));
        $this->addColumn('wicked_attachment_history', 'attachment_minorversion', 'integer', array('null' => false));
        $this->update('UPDATE wicked_attachment_history SET attachment_majorversion = attachment_version, attachment_minorversion = 0');
        $this->removeColumn('wicked_attachment_history', 'attachment_version');
        $this->addPrimaryKey('wicked_attachment_history', array('page_id', 'attachment_name', 'attachment_majorversion', 'attachment_minorversion'));
    }

    private function _rename($page, $name, $oldversion, $newversion)
    {
        try {
            $this->_vfs->rename(Wicked::VFS_ATTACH_PATH . '/' . $page,
                                $name . ';' . $oldversion,
                                Wicked::VFS_ATTACH_PATH . '/' . $page,
                                $name . ';' . $newversion);
        } catch (Exception $e) {
            $this->log('Cannot rename VFS file ' . Wicked::VFS_ATTACH_PATH
                       . '/' . $page . '/' . $name . ';' . $oldversion . ': '
                       . $e->getMessage());
        }
    }
}
