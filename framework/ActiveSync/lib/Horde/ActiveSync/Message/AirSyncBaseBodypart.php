<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseBodypart::
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
 * Horde_ActiveSync_Message_AirSyncBaseBodypart::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property integer        $status  The status property.
 *      Either Horde_ActiveSync_Status::BODYPART_CONVERSATION_TOO_LARGE or
 *      self::STATUS_SUCESS
 * @property integer        $type  The content type of the body.
 *     A Horde_ActiveSync::BODYPREF_TYPE_* constant.
 * @property integer        $estimateddatasize  The estimated size of the untruncated body.
 * @property integer        $truncated  The truncated flag. 0 == not truncated, 1 == truncated
 * @property string|stream  $data  The body data.
 * @property string         $preview  Body preview.
 */
class Horde_ActiveSync_Message_AirSyncBaseBodypart extends Horde_ActiveSync_Message_Base
{

    const STATUS_SUCCESS = 1;

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_STATUS            => array(self::KEY_ATTRIBUTE => 'status'),
        Horde_ActiveSync::AIRSYNCBASE_TYPE              => array(self::KEY_ATTRIBUTE => 'type'),
        Horde_ActiveSync::AIRSYNCBASE_ESTIMATEDDATASIZE => array(self::KEY_ATTRIBUTE => 'estimateddatasize'),
        Horde_ActiveSync::AIRSYNCBASE_TRUNCATED         => array(self::KEY_ATTRIBUTE => 'truncated'),
        Horde_ActiveSync::AIRSYNCBASE_DATA              => array(self::KEY_ATTRIBUTE => 'data'),
        Horde_ActiveSync::AIRSYNCBASE_PREVIEW           => array(self::KEY_ATTRIBUTE => 'preview')
    );

    /**
     * Property values
     *
     * @var array
     */
    protected $_properties = array(
        'status'            => false,
        'type'              =>  Horde_ActiveSync::BODYPREF_TYPE_HTML,
        'estimateddatasize' => false,
        'truncated'         => false,
        'data'              => false,
        'preview'           => false,
    );

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseBodypart';
    }

}