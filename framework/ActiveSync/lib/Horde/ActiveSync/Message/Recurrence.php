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
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
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

}