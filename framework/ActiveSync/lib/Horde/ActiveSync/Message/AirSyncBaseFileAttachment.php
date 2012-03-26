<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseFileAttachement class for transporting
 * attachment data during ITEMOPERATIONS requests.
 *
 * @copyright 2011 Horde LLC (http://www.horde.org)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseFileAttachment extends Horde_ActiveSync_Message_Base
{
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_CONTENTTYPE => array (self::KEY_ATTRIBUTE => 'contenttype'),
        Horde_ActiveSync_Request_ItemOperations::ITEMOPERATIONS_DATA => array (SKEY_ATTRIBUTE => '_data'),
    );

    protected $_properties = array(
        'contenttype',
        '_data',
    );

    public function getClass()
    {
        return 'AirSyncBaseFileAttachment';
    }

}
