<?php
/**
 * Horde_ActiveSync_Message_Attendee
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
 * Horde_ActiveSync_Message_Attendee
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
class Horde_ActiveSync_Message_Attendee extends Horde_ActiveSync_Message_Base
{
    /* Attendee Type Constants */
    const TYPE_REQUIRED     = 1;
    const TYPE_OPTIONAL     = 2;
    const TYPE_RESOURCE     = 3;

    /* Attendee Status */
    const STATUS_UNKNOWN    = 0;
    const STATUS_TENTATIVE  = 2;
    const STATUS_ACCEPT     = 3;
    const STATUS_DECLINE    = 4;
    const STATUS_NORESPONSE = 5;


    protected $_mapping = array(
        Horde_ActiveSync_Message_Appointment::POOMCAL_EMAIL => array (self::KEY_ATTRIBUTE => 'email'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_NAME  => array (self::KEY_ATTRIBUTE => 'name'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ATTENDEESTATUS => array(self::KEY_ATTRIBUTE => 'status'),
        Horde_ActiveSync_Message_Appointment::POOMCAL_ATTENDEETYPE => array(self::KEY_ATTRIBUTE => 'type')
    );

    protected $_properties = array(
        'email' => false,
        'name'  => false,
        'status' => false,
        'type' => false
    );

}