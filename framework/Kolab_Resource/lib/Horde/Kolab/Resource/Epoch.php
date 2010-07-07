<?php
/**
 * Handles Date conversion for the resource handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Handles Date conversion for the resource handler.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Epoch
{

    /**
     * The date to be converted.
     *
     * @var mixed
     */
    private $_date;

    /**
     * Constructor.
     *
     * @param mixed $date The date to be converted.
     */
    public function __construct($date)
    {
        $this->_date = $date;
    }

    /**
     * Clear information from a date array.
     *
     * @param array $ical_date  The array to clear.
     *
     * @return array The cleaned array.
     */
    private function cleanArray($ical_date)
    {
        if (!array_key_exists('hour', $ical_date)) {
            $temp['DATE'] = '1';
        }
        $temp['hour']   = array_key_exists('hour', $ical_date) ? $ical_date['hour'] :  '00';
        $temp['minute']   = array_key_exists('minute', $ical_date) ? $ical_date['minute'] :  '00';
        $temp['second']   = array_key_exists('second', $ical_date) ? $ical_date['second'] :  '00';
        $temp['year']   = array_key_exists('year', $ical_date) ? $ical_date['year'] :  '0000';
        $temp['month']   = array_key_exists('month', $ical_date) ? $ical_date['month'] :  '00';
        $temp['mday']   = array_key_exists('mday', $ical_date) ? $ical_date['mday'] :  '00';
        $temp['zone']   = array_key_exists('zone', $ical_date) ? $ical_date['zone'] :  'UTC';

        return $temp;
    }

    /**
     * Convert a date to an epoch.
     *
     * @param array  $values  The array to convert.
     *
     * @return int Time.
     */
    private function convert2epoch($values)
    {
        Horde::logMessage(sprintf('Converting to epoch %s',
                                  print_r($values, true)), 'DEBUG');

        if (is_array($values)) {
            $temp = $this->cleanArray($values);
            $epoch = gmmktime($temp['hour'], $temp['minute'], $temp['second'],
                              $temp['month'], $temp['mday'], $temp['year']);
        } else {
            $epoch=$values;
        }

        Horde::logMessage(sprintf('Converted <%s>', $epoch), 'DEBUG');
        return $epoch;
    }

    public function getEpoch()
    {
        return $this->convert2Epoch($this->_date);
    }
}