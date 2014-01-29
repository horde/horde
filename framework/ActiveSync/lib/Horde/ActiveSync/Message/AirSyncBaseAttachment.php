<?php
/**
 * Horde_ActiveSync_Message_AirSyncBaseAttachment::
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
 * Horde_ActiveSync_Message_AirSyncBaseAttachment::
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
 * @property string attmethod    The attachment method.
 * @property integer attsize     The attachment size.
 * @property string displayname  The attachment's display name.
 * @property string attname      The attachment's name.
 * @property boolean attremoved  @todo
 * @property contentid           The Content-Id of the mime part.
 * @property contentlocation     @todo
 * @property isinline            Indicates that this part is to be displayed
 *                               inline.
 */
class Horde_ActiveSync_Message_AirSyncBaseAttachment extends Horde_ActiveSync_Message_Base
{

    /* Attachement types */
    const ATT_TYPE_NORMAL   = 1;
    const ATT_TYPE_EMBEDDED = 5;
    const ATT_TYPE_OLE      = 6;

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

    /**
     * Property mapping.
     *
     * @var array
     */
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

    /**
     * Return the type of message.
     *
     * @return string
     */
    public function getClass()
    {
        return 'AirSyncBaseAttachment';
    }

}
