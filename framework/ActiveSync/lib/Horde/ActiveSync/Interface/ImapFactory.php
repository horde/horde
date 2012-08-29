<?php
/**
 * Horde_ActiveSync_Interface_Imap_Factory::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Interface_ImapFactory:: Defines an interface for a factory
 * object that knows how to provide an appropriate Horde_ActiveSync_Imap_Adapter
 * object and mailbox lists.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
interface Horde_ActiveSync_Interface_ImapFactory
{
    /**
     * Return an imap client object.
     *
     * @return mixed  An object capable of communicating with an IMAP server.
     */
    public function getImapOb();

    /**
     * Return the list of IMAP mailboxes
     *
     * @param boolean $force  If true, force a refresh of the list.
     *
     * @return array  An array of mailbox names.
     */
    public function getMailboxes($force = false);

    /**
     * Return the list of "special" mailboxes such as Trash/Sent
     *
     * @return array  An array of mailbox names, keyed by the special type.
     */
    public function getSpecialMailboxes();
}