<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Date extends Horde_View_Helper_Base
{
    private $_instanceTag = 'Horde_View_Helper_Form_InstanceTag_Date';

    /**
     * @todo possibly convert from time object
     */
    public function distanceOfTimeInWords($fromTime, $toTime = 0, $includeSeconds = false)
    {
        $distanceInMinutes = round(((abs($toTime - $fromTime)/60)));
        $distanceInSeconds = round(abs($toTime - $fromTime));

        if ($distanceInMinutes >= 0 && $distanceInMinutes <= 1) {
            if (! $includeSeconds) {
                return ($distanceInMinutes == 0) ? 'less than a minute' : '1 minute';
            }

            if ($distanceInSeconds >= 0 && $distanceInSeconds <= 4) {
                return 'less than 5 seconds';
            } else if ($distanceInSeconds >= 5 && $distanceInSeconds <= 9) {
                return 'less than 10 seconds';
            } else if ($distanceInSeconds >= 10 && $distanceInSeconds <= 19) {
                return 'less than 20 seconds';
            } else if ($distanceInSeconds >= 20 && $distanceInSeconds <= 39) {
                return 'half a minute';
            } else if ($distanceInSeconds >= 40 && $distanceInSeconds <= 59) {
                return 'less than a minute';
            } else {
                return '1 minute';
            }
        } else if ($distanceInMinutes >= 2 && $distanceInMinutes <= 44) {
            return "$distanceInMinutes minutes";
        } else if ($distanceInMinutes >= 45 && $distanceInMinutes <= 89) {
            return 'about 1 hour';
        } else if ($distanceInMinutes >= 90 && $distanceInMinutes <= 1439) {
            return 'about ' . round($distanceInMinutes / 60) . ' hours';
        } else if ($distanceInMinutes >= 1440 && $distanceInMinutes <= 2879) {
            return '1 day';
        } else if ($distanceInMinutes >= 2880 && $distanceInMinutes <= 43199) {
            return intval($distanceInMinutes / 1440) . ' days';
        } else if ($distanceInMinutes >= 43200 && $distanceInMinutes <= 86399) {
            return 'about 1 month';
        } else if ($distanceInMinutes >= 86400 && $distanceInMinutes <= 525959) {
            return round(($distanceInMinutes / 43200)) . ' months';
        } else if ($distanceInMinutes >= 525960 && $distanceInMinutes <= 1051919) {
            return 'about 1 year';
        } else {
            return 'over ' . round($distanceInMinutes / 525600) . ' years';
        }
    }

    /**
     * Like distance_of_time_in_words, but where <tt>to_time</tt> is fixed to
     * <tt>Time.now</tt>.
     */
    public function timeAgoInWords($fromTime, $includeSeconds=false)
    {
        return $this->distanceOfTimeInWords($fromTime, time(), $includeSeconds);
    }

    /**
     * alias method to timeAgoInWords
     */
    public function distanceOfTimeInWordsToNow($fromTime, $includeSeconds=false)
    {
        return $this->timeAgoInWords($fromTime, $includeSeconds);
    }

    public function dateSelect($objectName, $method, $options = array())
    {
        $object = isset($options['object']) ? $options['object'] : null;
        unset($options['object']);
        $tag = new $this->_instanceTag($objectName, $method, $this->_view, $object);
        return $tag->toDateSelectTag($options);
    }
}
