<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseBody::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_AirSyncBaseBody::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2011-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property integer   $type  The content type of the body.
 *     A Horde_ActiveSync::BODYPREF_TYPE_* constant.
 * @property integer   $estimateddatasize  The estimated size of the untruncated body.
 * @property integer   $truncated  The truncated flag. 0 == not truncated, 1 == truncated
 * @property mixed   $string|stream  $data  The body data.
 */
class Horde_ActiveSync_Message_AirSyncBaseBody extends Horde_ActiveSync_Message_Base
{
    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array(
        Horde_ActiveSync::AIRSYNCBASE_TYPE              => array(self::KEY_ATTRIBUTE => 'type'),
        Horde_ActiveSync::AIRSYNCBASE_ESTIMATEDDATASIZE => array(self::KEY_ATTRIBUTE => 'estimateddatasize'),
        Horde_ActiveSync::AIRSYNCBASE_TRUNCATED         => array(self::KEY_ATTRIBUTE => 'truncated'),
        Horde_ActiveSync::AIRSYNCBASE_DATA              => array(self::KEY_ATTRIBUTE => 'data'),
    );

    /**
     * Property values
     *
     * @var array
     */
    protected $_properties = array(
        'type'              => false,
        'estimateddatasize' => false,
        'truncated'         => false,
        'data'              => false
    );

    /**
     * Const'r
     *
     * @see Horde_ActiveSync_Message_Base::__construct()
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ($this->_version >= Horde_ActiveSync::VERSION_FOURTEEN) {
            $this->_mapping += array(
                Horde_ActiveSync::AIRSYNCBASE_PREVIEW => array(self::KEY_ATTRIBUTE => 'preview')
            );
            $this->_properties += array(
                'preview' => false
            );
        }
    }

    /**
     * Return the message type.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseBody';
    }
}