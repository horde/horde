<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseBody
 *
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseBody extends Horde_ActiveSync_Message_Base
{
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_TYPE              => array(self::KEY_ATTRIBUTE => 'type'),
        Horde_ActiveSync::AIRSYNCBASE_ESTIMATEDDATASIZE => array(self::KEY_ATTRIBUTE => 'estimateddatasize'),
        Horde_ActiveSync::AIRSYNCBASE_TRUNCATED         => array(self::KEY_ATTRIBUTE => 'truncated'),
        Horde_ActiveSync::AIRSYNCBASE_DATA              => array(self::KEY_ATTRIBUTE => 'data'),
    );

    protected $_properties = array(
        'type'              => false,
        'estimateddatasize' => false,
        'truncated'         => false,
        'data'              => false
    );
}