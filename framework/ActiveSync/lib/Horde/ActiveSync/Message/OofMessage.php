<?php
/**
 * Horde_ActiveSync_Message_OofMessage::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 */
/**
 * Horde_ActiveSync_Message_OofMessage::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 *
 * @property boolean $internal
 * @property boolean $externalknown
 * @property boolean $externalunknown
 * @property boolean $enabled
 * @property string  $reply
 * @property string  $bodytype
 */
class Horde_ActiveSync_Message_OofMessage extends Horde_ActiveSync_Message_Base
{

    public $internal;
    public $externalknown;
    public $externalunknown;

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync_Request_Settings::SETTINGS_APPLIESTOINTERNAL   => array(self::KEY_ATTRIBUTE => 'internal'),
        Horde_ActiveSync_Request_Settings::SETTINGS_APPLIESTOEXTERNALKNOWN  => array(self::KEY_ATTRIBUTE => 'externalknown'),
        Horde_ActiveSync_Request_Settings::SETTINGS_APPLIESTOEXTERNALUNKNOWN    => array(self::KEY_ATTRIBUTE => 'externalunknown'),
        Horde_ActiveSync_Request_Settings::SETTINGS_ENABLED => array(self::KEY_ATTRIBUTE => 'enabled'),
        Horde_ActiveSync_Request_Settings::SETTINGS_REPLYMESSAGE   => array(self::KEY_ATTRIBUTE => 'reply'),
        Horde_ActiveSync_Request_Settings::SETTINGS_BODYTYPE => array(self::KEY_ATTRIBUTE => 'bodytype')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'enabled' => false,
        'reply'   => false,
        'bodytype' => false,
    );

    /**
     * Checks to see if we should send an empty value.
     *
     * @param string $tag  The tag name
     *
     * @return boolean
     */
    protected function _checkSendEmpty($tag)
    {
        return true;
    }
}