<?php
/**
 * Horde_ActiveSync_Message_Document:: Defines an object representing a single
 * DOCUMENTLIBRARY object, as returned in an ITEMOPERATIONS response.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2014-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_DocumentLibrary
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2014-2017 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 */
class Horde_ActiveSync_Message_Document extends Horde_ActiveSync_Message_AirSyncBaseFileAttachment
{
    /**
     * Property map
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_RANGE   => array(self::KEY_ATTRIBUTE => 'range'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_TOTAL   => array(self::KEY_ATTRIBUTE => 'total'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_DATA    => array(self::KEY_ATTRIBUTE => 'data'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_VERSION => array(self::KEY_ATTRIBUTE => 'version', self::KEY_TYPE => self::TYPE_DATE),
    );

    /**
     * Property values
     *
     * @var array
     */
    protected $_properties = array(
        'range'   => false,
        'total'   => false,
        'data'    => false,
        'version' => false
    );

}
