<?php
/**
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */

/**
 * Base class for different strategies to fetch changes from an IMAP server.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_Imap_Strategy_Base
{
    /**
     * The IMAP status array.
     *
     * @var array
     */
    protected $_status;

    /**
     * The folder object.
     *
     * @var Horde_ActiveSync_Folder_Base
     */
    protected $_folder;

    /**
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * The process id for logging purposes.
     *
     * @var integer
     */
    protected $_procid;

    /**
     * The current mailbox.
     *
     * @var Horde_Imap_Client_Mailbox
     */
    protected $_mbox;

    /**
     * The imap client object.
     *
     * @var Horde_Imap_Client_Base
     */
    protected $_imap_ob;

    /**
     * The imap factory. Needed to get list of custom flags.
     *
     * @var Horde_ActiveSync_Interface_ImapFactory
     */
    protected $_imap;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync_Interface_ImapFactory $imap The IMAP factory.
     * @param array $status                         The IMAP status array.
     * @param Horde_ActiveSync_Folder_Base $folder  The folder object.
     * @param Horde_Log_Logger $logger              The logger.
     */
    public function __construct(
        Horde_ActiveSync_Interface_ImapFactory $imap,
        array $status,
        Horde_ActiveSync_Folder_Base $folder,
        $logger)
    {
        $this->_imap = $imap;
        $this->_imap_ob = $imap->getImapOb();
        $this->_status = $status;
        $this->_folder = $folder;
        $this->_logger = $logger;
        $this->_procid = getmypid();
        $this->_mbox = new Horde_Imap_Client_Mailbox($folder->serverid());
    }

    /**
     * Return a folder object containing all IMAP server change information.
     *
     * @param array $options  An array of options.
     *        @see Horde_ActiveSync_Imap_Adapter::getMessageChanges
     *
     * @return Horde_ActiveSync_Folder_Base  The populated folder object.
     */
    abstract public function getChanges(array $options);

    /**
     * Return an array of custom IMAP flags.
     *
     * @return array
     */
    protected function _getMsgFlags()
    {
        // @todo Horde_ActiveSync 3.0 remove method_exists check.
        if (method_exists($this->_imap, 'getMsgFlags')) {
            return $this->_imap->getMsgFlags();
        }

        return array();
    }

}