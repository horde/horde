<?php
/**
 * Horde_ActiveSync_Message_Recurrence::
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
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Message_Recurrence::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 *
 * @property integer    type
 * @property Horde_Date until
 * @property string     occurrences
 * @property integer    interval
 * @property integer    dayofweek
 * @property integer    dayofmonth
 * @property integer    weekofmonth
 * @property integer    monthofyear
 */
class Horde_ActiveSync_Message_Recurrence extends Horde_ActiveSync_Message_Base
{
    /* MS AS Recurrence types */
    const TYPE_DAILY       = 0;
    const TYPE_WEEKLY      = 1;
    const TYPE_MONTHLY     = 2;
    const TYPE_MONTHLY_NTH = 3;
    const TYPE_YEARLY      = 5;
    const TYPE_YEARLYNTH   = 6;

    const CALENDAR_TYPE_DEFAULT                  = 0;
    const CALENDAR_TYPE_GREGORIAN                = 1;
    const CALENDAR_TYPE_GREGORIAN_US             = 2;
    const CALENDAR_TYPE_JAPANESE                 = 3;
    const CALENDAR_TYPE_TAIWAN                   = 4;
    const CALENDAR_TYPE_KOREAN                   = 5;
    const CALENDAR_TYPE_HIJRI                    = 6;
    const CALENDAR_TYPE_THAI                     = 7;
    const CALENDAR_TYPE_HEBREW                   = 8;
    const CALENDAR_TYPE_GREGORIAN_FRENCH         = 9;
    const CALENDAR_TYPE_GREGORIAN_ARABIC         = 10;
    const CALENDAR_TYPE_GREGORIAN_TRANSLITERATED = 11;

    /**
     * Property mapping.
     *
     * @var array
     */
    protected $_mapping = array (
        Horde_ActiveSync_Message_Appointment::POOMCAL_TYPE        => array (self::KEY_ATTRIBUTE => 'type'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_UNTIL       => array (self::KEY_ATTRIBUTE => 'until', self::KEY_TYPE => self::TYPE_DATE),
        Horde_ActiveSync_Message_Appointment::POOMCAL_OCCURRENCES => array (self::KEY_ATTRIBUTE => 'occurrences'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_INTERVAL    => array (self::KEY_ATTRIBUTE => 'interval'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_DAYOFWEEK   => array (self::KEY_ATTRIBUTE => 'dayofweek'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_DAYOFMONTH  => array (self::KEY_ATTRIBUTE => 'dayofmonth'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_WEEKOFMONTH => array (self::KEY_ATTRIBUTE => 'weekofmonth'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_MONTHOFYEAR => array (self::KEY_ATTRIBUTE => 'monthofyear')
    );

    /**
     * Property values.
     *
     * @var array
     */
    protected $_properties = array(
        'type'        => false,
        'until'       => false,
        'occurrences' => false,
        'interval'    => false,
        'dayofweek'   => false,
        'dayofmonth'  => false,
        'weekofmonth' => false,
        'monthofyear' => false,
    );

    /**
     * Const'r
     *
     * @param array $options  Configuration options for the message:
     *   - logger: (Horde_Log_Logger)  A logger instance
     *             DEFAULT: none (No logging).
     *   - protocolversion: (float)  The version of EAS to support.
     *              DEFAULT: Horde_ActiveSync::VERSION_TWOFIVE (2.5)
     *
     * @return Horde_ActiveSync_Message_Base
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);

        if ($this->_version >= Horde_ActiveSync::VERSION_FOURTEEN) {
            $this->_mapping += array(
                Horde_ActiveSync_Message_Appointment::POOMCAL_CALENDARTYPE => array(self::KEY_ATTRIBUTE => 'calendartype'),
                Horde_ActiveSync_Message_Appointment::POOMCAL_ISLEAPMONTH => array(self::KEY_ATTRIBUTE => 'isleapmonth'));

            $this->_properties += array(
                'calendartype' => false,
                'isleapmonth' => false);
        }
    }







}