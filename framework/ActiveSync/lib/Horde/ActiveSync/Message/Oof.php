<?php
/**
 * Horde_ActiveSync_Message_Oof::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 */
/**
 * Horde_ActiveSync_Message_Oof::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 *
 * @property integer                             $state
 * @property Horde_Date                          $starttime
 * @property Horde_Date                          $endtime
 * @property Horde_ActiveSync_Message_OofMessage $message
 * @property string                              $bodytype
 */
class Horde_ActiveSync_Message_Oof extends Horde_ActiveSync_Message_Base
{

    public $messages = array();

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync_Request_Settings::SETTINGS_OOFSTATE => array(self::KEY_ATTRIBUTE => 'state'),
        Horde_ActiveSync_Request_Settings::SETTINGS_STARTTIME => array(self::KEY_ATTRIBUTE => 'starttime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Request_Settings::SETTINGS_ENDTIME => array(self::KEY_ATTRIBUTE => 'endtime', self::KEY_TYPE => self::TYPE_DATE_DASHES),
        Horde_ActiveSync_Request_Settings::SETTINGS_OOFMESSAGE => array(
            self::KEY_ATTRIBUTE => 'messages',
            self::KEY_TYPE => 'Horde_ActiveSync_Message_OofMessage',
            self::KEY_VALUES => Horde_ActiveSync_Request_Settings::SETTINGS_OOFMESSAGE,
            self::KEY_PROPERTY => self::PROPERTY_NO_CONTAINER
        ),
        Horde_ActiveSync_Request_Settings::SETTINGS_BODYTYPE => array(self::KEY_ATTRIBUTE => 'bodytype'),
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'state'   => false,
        'starttime'  => false,
        'endtime'    => false,
        'bodytype'   => false
    );

}