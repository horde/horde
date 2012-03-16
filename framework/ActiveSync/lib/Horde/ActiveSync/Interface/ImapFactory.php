<?php
/**
 * Horde_ActiveSync_Interface_ImapFactory
 *
 * PHP Version 5
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Interface_ImapFactory:: Defines an interface for a factory
 * object that knows how to provide an appropriate Horde_ActiveSync_Imap_Adapter
 * object and mailbox lists.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2010-2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */

interface Horde_ActiveSync_Interface_ImapFactory
{
    public function getImapOb();

    public function getMailboxes();

    public function getSpecialMailboxes();
}