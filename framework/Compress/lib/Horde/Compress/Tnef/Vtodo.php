<?php
/**
 * Object to parse and represent vTODO data encapsulated in a TNEF file.
 *
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
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
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 */
class Horde_Compress_Tnef_vTodo extends Horde_Compress_Tnef_Object
{
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
        case Horde_Compress_Tnef::IPM_TASK_GUID:
            // Almost positive this is wrong :(
            $this->guid = Horde_Mapi::getUidFromGoid(bin2hex($value));
            break;
        case Horde_Compress_Tnef::MSG_EDITOR_FORMAT:
            // Map this?
            $this->msgformat = $value;
            break;
        case Horde_Compress_Tnef::MAPI_TAG_SYNC_BODY:
            //rtfsyncbody
            break;
        case Horde_Compress_Tnef::MAPI_TAG_HTML:
            //htmlbody
            break;
        case Horde_Compress_Tnef::MAPI_TASK_DUEDATE:
            $this->due = new Horde_Date(Horde_Mapi::filetimeToUnixtime($value));
            break;
        }
    }

    /**
     * Output the data for this object in an array.
     *
     * @return array
     *   - type: (string)    The MIME type of the content.
     *   - subtype: (string) The MIME subtype.
     *   - name: (string)    The filename.
     *   - stream: (string)  The file data.
     */
    public function toArray()
    {
    }

}