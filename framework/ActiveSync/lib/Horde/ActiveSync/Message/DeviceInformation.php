<?php
/**
 * Horde_ActiveSync_Message_DeviceInformation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2013-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 */
/**
 * Horde_ActiveSync_Message_DeviceInformation::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2014 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 * @since     2.21.0
 *
 * @property string $model
 * @property string $imei
 * @property string $friendlyname
 * @property string $os
 * @property string $oslanguage
 * @property string $phonenumber
 * @property string $useragent
 * @property string $mobileoperator
 * @property string $enableoutboundsms
 */
class Horde_ActiveSync_Message_DeviceInformation extends Horde_ActiveSync_Message_Base
{

    /**
     * Property mapping
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync_Request_Settings::SETTINGS_MODEL             => array(self::KEY_ATTRIBUTE => 'model'),
        Horde_ActiveSync_Request_Settings::SETTINGS_IMEI              => array(self::KEY_ATTRIBUTE => 'imei'),
        Horde_ActiveSync_Request_Settings::SETTINGS_FRIENDLYNAME      => array(self::KEY_ATTRIBUTE => 'friendlyname'),
        Horde_ActiveSync_Request_Settings::SETTINGS_OS                => array(self::KEY_ATTRIBUTE => 'os'),
        Horde_ActiveSync_Request_Settings::SETTINGS_OSLANGUAGE        => array(self::KEY_ATTRIBUTE => 'oslanguage'),
        Horde_ActiveSync_Request_Settings::SETTINGS_PHONENUMBER       => array(self::KEY_ATTRIBUTE => 'phonenumber'),
        Horde_ActiveSync_Request_Settings::SETTINGS_USERAGENT         => array(self::KEY_ATTRIBUTE => 'useragent'),
        Horde_ActiveSync_Request_Settings::SETTINGS_MOBILEOPERATOR    => array(self::KEY_ATTRIBUTE => 'mobileoperator'),
        Horde_ActiveSync_Request_Settings::SETTINGS_ENABLEOUTBOUNDSMS => array(self::KEY_ATTRIBUTE => 'enableoutboundsms')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'model'             => false,
        'imei'              => false,
        'friendlyname'      => false,
        'os'                => false,
        'oslanguage'        => false,
        'phonenumber'       => false,
        'useragent'         => false,
        'mobileoperator'    => false,
        'enableoutboundsms' => false
    );

}