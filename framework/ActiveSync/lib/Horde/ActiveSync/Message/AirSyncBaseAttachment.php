<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseAttachement class represents a single attachemnt.
 *
 * @copyright 2011 Horde LLC (http://www.horde.org)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @package ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseAttachment extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_DISPLAYNAME       => array (self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::AIRSYNCBASE_FILEREFERENCE     => array (self::KEY_ATTRIBUTE => 'attname'),
        Horde_ActiveSync::AIRSYNCBASE_METHOD            => array (self::KEY_ATTRIBUTE => 'attmethod'),
        Horde_ActiveSync::AIRSYNCBASE_ESTIMATEDDATASIZE => array (self::KEY_ATTRIBUTE => 'attsize'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENTID         => array (self::KEY_ATTRIBUTE => 'contentid'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENTLOCATION   => array (self::KEY_ATTRIBUTE => 'contentlocation'),
        Horde_ActiveSync::AIRSYNCBASE_ISINLINE          => array (self::KEY_ATTRIBUTE => 'isinline'),
        Horde_ActiveSync::AIRSYNCBASE_DATA              => array (self::KEY_ATTRIBUTE => '_data'),
    );

    protected $_properties = array(
        'attmethod'       => false,
        'attsize'         => false,
        'displayname'     => false,
        'attname'         => false,
        'attremoved'      => false,
        'contentid'       => false,
        'contentlocation' => false,
        'isinline'        => false,
        '_data'           => false
    );

    public function getClass()
    {
        return 'AirSyncBaseAttachment';
    }

}
