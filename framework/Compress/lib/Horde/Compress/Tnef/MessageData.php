<?php
/**
 * Object for holding properties of the TNEF message as a whole.
 *
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
 class Horde_Compress_Tnef_MessageData extends Horde_Compress_Tnef_Object
 {
    /**
     *
     * @var string
     */
    public $subject;

    /**
     *
     * @var Horde_Date
     */
    public $dateSent;

    /**
     *
     * @var string
     */
    public $from;

    /**
     *
     * @var string
     */
    public $fromName;

    /**
     * Allow this object to set any TNEF attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $attribute  The attribute descriptor.
     * @param mixed $value        The value from the MAPI stream.
     * @param integer $size       The byte length of the data, as reported by
     *                            the MAPI data.
     */
    public function setTnefAttribute($attribute, $value, $size)
    {
        switch ($attribute) {
        case Horde_Compress_Tnef::ASUBJECT:
            $this->subject = trim($value);
            break;

        case Horde_Compress_Tnef::ADATERECEIVED:
            if (!$this->dateSent) {
                $this->dateSent = new Horde_Compress_Tnef_Date($value);
            }
            break;

        case Horde_Compress_Tnef::ADATESENT:
            $this->dateSent = new Horde_Compress_Tnef_Date($value);
            break;
        }
    }

    /**
     * Allow this object to set any MAPI attributes it needs to know about,
     * ignore any it doesn't care about.
     *
     * @param integer $type  The attribute type descriptor.
     * @param integer $name  The attribute name descriptor.
     */
    public function setMapiAttribute($type, $name, $value)
    {
        switch ($name) {
        case Horde_Compress_Tnef::MAPI_CONVERSATION_TOPIC:
            $this->subject = $value;
            break;

        case Horde_Compress_Tnef::MAPI_SENT_REP_EMAIL_ADDR:
            $this->from = $value;
            break;

        case Horde_Compress_Tnef::MAPI_SENT_REP_NAME:
            $this->fromName = $value;
        }
    }

 }