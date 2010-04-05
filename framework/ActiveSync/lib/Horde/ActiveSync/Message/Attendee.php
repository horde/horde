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
    const STATUS_TENATIVE = 2;
    const STATUS_ACCEPT = 3;
    const STATUS_DECLINE = 4;
    const STATUS_NORESPONSE = 5;

    /**
     * Const'r
     *
     * @param array $params
     */
    function __construct($params) {
        $mapping = array(
            SYNC_POOMCAL_EMAIL => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'email'),
            SYNC_POOMCAL_NAME => array (Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'name'),
            SYNC_POOMCAL_ATTENDEETYPE => array(Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'type'),
            SYNC_POOMCAL_ATTENDEESTATUS => array(Horde_ActiveSync_Message_Base::KEY_ATTRIBUTE => 'status')
        );

        parent::__construct($mapping, $params);
    }

}