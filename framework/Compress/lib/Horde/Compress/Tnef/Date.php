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
 * Object to parse and represent a date encapsulated by a TNEF file.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress
 */
class Horde_Compress_Tnef_Date extends Horde_Compress_Tnef_Object
{
    public $date;

    public function __construct($data)
    {
        $year = $this->_geti($data, 16);
        $month = $this->_geti($data, 16);
        $day = $this->_geti($data, 16);
        $hour = $this->_geti($data, 16);
        $minute = $this->_geti($data, 16);
        $second = $this->_geti($data, 16);

        try {
            $this->date = new Horde_Date(
                sprintf(
                    '%04d-%02d-%02d %02d:%02d:%02d',
                    $year, $month, $day, $hour, $minute, $second)
            );
        } catch (Horde_Date_Exception $e) {
            throw new Horde_Compress_Exception($e);
        }
    }
}