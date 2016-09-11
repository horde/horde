<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseAdd::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncBaseAdd::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Message_AirSyncBaseAdd extends Horde_ActiveSync_Message_Base
{

    /**
     * Property mappings
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_CLIENTID => array(self::KEY_ATTRIBUTE => 'clientid'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENT => array(self::KEY_ATTRIBUTE => 'content'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENTID => array(self::KEY_ATTRIBUTE => 'contentid'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENTLOCATION => array(self::KEY_ATTRIBUTE => 'contentlocation'),
        Horde_ActiveSync::AIRSYNCBASE_CONTENTTYPE => array(self::KEY_ATTRIBUTE => 'contenttype'),
        Horde_ActiveSync::AIRSYNCBASE_DISPLAYNAME => array(self::KEY_ATTRIBUTE => 'displayname'),
        Horde_ActiveSync::AIRSYNCBASE_ISINLINE => array(self::KEY_ATTRIBUTE => 'isinline'),
        Horde_ActiveSync::AIRSYNCBASE_METHOD => array(self::KEY_ATTRIBUTE => 'method')
    );

    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_properties = array(
        'clientid' => false,
        'content' => false,
        'contentid' => false,
        'contentlocation' => false,
        'contenttype' => false,
        'displayname' => false,
        'isinline' => false,
        'method' => false,
    );

    /**
     * Return the type of message.
     *
     * @return string
     * @deprecated
     */
    public function getClass()
    {
        return 'AirSyncBaseAdd';
    }

}
