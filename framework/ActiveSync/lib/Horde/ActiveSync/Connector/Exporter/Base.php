<?php
/**
 * Horde_ActiveSync_Connector_Exporter_Base::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Connector_Exporter_Base:: Base class contains common
 * code for outputing common blocks of WBXML data in server responses.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
abstract class Horde_ActiveSync_Connector_Exporter_Base
{
    /**
     * The wbxml encoder
     *
     * @var Horde_ActiveSync_Wbxml_Encoder
     */
    protected $_encoder;

    /**
     * Local cache of changes to send.
     *
     * @var array
     */
    protected $_changes = array();

    /**
     * Counter of changes sent.
     *
     * @var integer
     */
    protected $_step = 0;

    /**
     * The ActiveSync server object.
     *
     * @var Horde_ActiveSync
     */
    protected $_as;

    /**
     * Process id for logging.
     *
     * @var integer
     */
    protected $_procid;

    /**
     * Const'r
     *
     * @param Horde_ActiveSync $as                    The ActiveSync server.
     * @param Horde_ActiveSync_Wbxml_Encoder $encoder The encoder
     *
     * @return Horde_ActiveSync_Connector_Exporter
     */
    public function __construct(
        Horde_ActiveSync $as,
        Horde_ActiveSync_Wbxml_Encoder $encoder = null)
    {
        $this->_as = $as;
        $this->_encoder = $encoder;
        $this->_logger = $as->logger;
        $this->_procid = getmypid();
    }

    /**
     * Set the changes to send to the client.
     *
     * @param array $changes  The changes array returned from the collection
     *                        handler.
     * @param array $collection  The collection we are currently syncing.
     */
    public function setChanges($changes, $collection = null)
    {
        $this->_changes = $changes;
        $this->_seenObjects = array();
        $this->_step = 0;
    }

    /**
     * Sends the next change in the set to the client.
     *
     * @return boolean|Horde_Exception True if more changes can be sent false if
     *                                 all changes were sent, Horde_Exception if
     *                                 there was an error sending an item.
     */
    abstract public function sendNextChange();
}