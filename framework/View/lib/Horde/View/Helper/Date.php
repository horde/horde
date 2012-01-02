<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Date extends Horde_View_Helper_Base
{
    private $_instanceTag = 'Horde_View_Helper_Form_InstanceTag_Date';

    /**
     * @todo possibly convert from time object
     */
    public function distanceOfTimeInWords($fromTime, $toTime = 0,
                                          $includeSeconds = false)
    {
        $distanceInMinutes = round(abs($toTime - $fromTime) / 60);
        $distanceInSeconds = round(abs($toTime - $fromTime));

        if ($distanceInMinutes >= 0 && $distanceInMinutes <= 1) {
            if (!$includeSeconds) {
                return ($distanceInMinutes == 0)
                    ? 'less than a minute'
                    : '1 minute';
            }

            if ($distanceInSeconds >= 0 && $distanceInSeconds <= 4) {
                return 'less than 5 seconds';
            } elseif ($distanceInSeconds >= 5 && $distanceInSeconds <= 9) {
                return 'less than 10 seconds';
            } elseif ($distanceInSeconds >= 10 && $distanceInSeconds <= 19) {
                return 'less than 20 seconds';
            } elseif ($distanceInSeconds >= 20 && $distanceInSeconds <= 39) {
                return 'half a minute';
            } elseif ($distanceInSeconds >= 40 && $distanceInSeconds <= 59) {
                return 'less than a minute';
            } else {
                return '1 minute';
            }
        } elseif ($distanceInMinutes >= 2 && $distanceInMinutes <= 44) {
            return "$distanceInMinutes minutes";
        } elseif ($distanceInMinutes >= 45 && $distanceInMinutes <= 89) {
            return 'about 1 hour';
        } elseif ($distanceInMinutes >= 90 && $distanceInMinutes <= 1439) {
            return 'about ' . round($distanceInMinutes / 60) . ' hours';
        } elseif ($distanceInMinutes >= 1440 && $distanceInMinutes <= 2879) {
            return '1 day';
        } elseif ($distanceInMinutes >= 2880 && $distanceInMinutes <= 43199) {
            return intval($distanceInMinutes / 1440) . ' days';
        } elseif ($distanceInMinutes >= 43200 && $distanceInMinutes <= 86399) {
            return 'about 1 month';
        } elseif ($distanceInMinutes >= 86400 && $distanceInMinutes <= 525959) {
            return round(($distanceInMinutes / 43200)) . ' months';
        } elseif ($distanceInMinutes >= 525960 && $distanceInMinutes <= 1051919) {
            return 'about 1 year';
        } else {
            return 'over ' . round($distanceInMinutes / 525600) . ' years';
        }
    }

    /**
     * Like distanceOfTimeInWords(), but where $toTime is fixed to now.
     */
    public function timeAgoInWords($fromTime, $includeSeconds = false)
    {
        return $this->distanceOfTimeInWords($fromTime, time(), $includeSeconds);
    }

    public function dateSelect($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toDateSelectTag($options);
    }
}
