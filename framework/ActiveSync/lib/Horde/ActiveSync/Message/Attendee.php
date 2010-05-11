<?php
/**
 * Horde_ActiveSync_Message_Attendee class represents a single ActiveSync
 * Attendee sub-object.
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Message_Attendee extends Horde_ActiveSync_Message_Base
{
    /* Attendee Type Constants */
    const TYPE_REQUIRED = 1;
    const TYPE_OPTIONAL = 2;
    const TYPE_RESOURCE = 3;

    /* Attendee Status */
    const STATUS_UNKNOWN = 0;
    const STATUS_TENTATIVE = 2;
    const STATUS_ACCEPT = 3;
    const STATUS_DECLINE = 4;
    const STATUS_NORESPONSE = 5;

    /**
     * Const'r
     *
     * @param array $params
     */
    function __construct($params = array()) {
        $this->_mapping = array(
            Horde_ActiveSync_Message_Appointment::POOMCAL_EMAIL => array (self::KEY_ATTRIBUTE => 'email'),
            Horde_ActiveSync_Message_Appointment::POOMCAL_NAME => array (self::KEY_ATTRIBUTE => 'name'),
            //SYNC_POOMCAL_ATTENDEETYPE => array(self::KEY_ATTRIBUTE => 'type'),
            //SYNC_POOMCAL_ATTENDEESTATUS => array(self::KEY_ATTRIBUTE => 'status')
        );

        $this->_properties = array(
            'email' => false,
            'name' => false,
        );

        parent::__construct($params);
    }

}