<?php
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

/**
 * Base object for any files/attachments encapsulated in a TNEF file.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress
 */
class Horde_Compress_Tnef_Object
{
    /**
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     *
     * @var string
     */
    protected $_data;

    /**
     *
     * @var array
     */
    protected $_options;


    public function __construct($logger, $data = null, $options = array())
    {
        $this->_data = $data;
        $this->_logger = $logger;
        $this->_options = $options;
    }

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

    /**
     * Pop specified number of bytes from the buffer.
     *
     * @param string &$data  The data string.
     * @param integer $bytes  How many bytes to retrieve.
     *
     * @return string  The specified number of bytes from $data.
     */
    protected function _getx(&$data, $bytes)
    {
        $value = null;

        if (strlen($data) >= $bytes) {
            $value = substr($data, 0, $bytes);
            $data = substr_replace($data, '', 0, $bytes);
        }

        return $value;
    }

    /**
     * Pop specified number of bits from the buffer
     *
     * @param string &$data  The data string.
     * @param integer $bits  How many bits to retrieve.
     *
     * @return integer  The value from $data.
     */
    protected function _geti(&$data, $bits)
    {
        $bytes = $bits / 8;
        $value = null;

        if (strlen($data) >= $bytes) {
            $value = ord($data[0]);
            if ($bytes >= 2) {
                $value += (ord($data[1]) << 8);
            }
            if ($bytes >= 4) {
                $value += (ord($data[2]) << 16) + (ord($data[3]) << 24);
            }
            $data = substr_replace($data, '', 0, $bytes);
        }

        return $value;
    }

}