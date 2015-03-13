<?php
/**
 *
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