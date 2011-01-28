<?php
/**
 * Change positive integer columns to unsigned.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Wicked
 */
class WickedUnsignedInts extends Horde_Db_Migration_Base
{
    /**
     * Upgrade.
     */
    public function up()
    {
        $this->changeColumn('wicked_pages', 'page_id', 'integer', array('autoincrement' => true, 'null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_pages', 'page_hits', 'integer', array('default' => 0, 'unsigned' => true));
        $this->changeColumn('wicked_pages', 'page_majorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_pages', 'page_minorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_pages', 'version_created', 'integer', array('null' => false, 'unsigned' => true));

        $this->changeColumn('wicked_history', 'page_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_history', 'page_majorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_history', 'page_minorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_history', 'version_created', 'integer', array('null' => false, 'unsigned' => true));

        $this->changeColumn('wicked_attachments', 'page_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachments', 'attachment_hits', 'integer', array('default' => 0, 'unsigned' => true));
        $this->changeColumn('wicked_attachments', 'attachment_majorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachments', 'attachment_minorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachments', 'attachment_created', 'integer', array('null' => false, 'unsigned' => true));

        $this->changeColumn('wicked_attachment_history', 'page_id', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachment_history', 'attachment_majorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachment_history', 'attachment_minorversion', 'integer', array('null' => false, 'unsigned' => true));
        $this->changeColumn('wicked_attachment_history', 'attachment_created', 'integer', array('null' => false, 'unsigned' => true));
    }

    /**
     * Downgrade.
     */
    public function down()
    {
        // Don't need to undo these changes, they are non-destructive.
    }
}
