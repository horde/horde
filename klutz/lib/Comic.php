<?php
/**
 * Klutz Comic Class.
 *
 * @author Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz_Comic
{
    /**
     * The title of the comics (Dilbert, The 5th Wave, etc.)
     *
     * @var string
     */
    var $name = null;

    /**
     * The author or authors of the comic (the byline)
     *
     * @var string
     */
    var $author = null;

    /**
     * The URL for the official homepage (not necessarily where we
     * get the comic from.
     *
     * @var string
     */
    var $homepage = null;

    /**
     * Days (lowercase, three-letter english abbreviation) that this comic is
     * is available.
     *
     * @var array
     */
    var $days = array('mon','tue','wed','thu','fri','sat','sun');

    /**
     * Some comment to display for this comic
     *
     * @var string
     */
    var $comment = null;

    /**
     * Days behind the current date this comic is published
     *
     * @var string
     */
    var $offset = 0;

    /**
     * Are past episodes available?  Some comics are difficult or
     * impossible to retrieve other than the day it's published.
     *
     * @var boolean
     */
    var $nohistory = false;

    /*
     * Parameters specific to fetching or otherwise processing the comic
     */

    /**
     * Web browser object used to fetch pages
     *
     * @var HTTP_Request
     */
    var $http = null;

    /**
     * The first url we need to hit to get the comic we want.
     *
     * @var string
     */
    var $url;

    /**
     * Headers we need to pass/override to be able to get the comic.
     * These are passed to HTTP_Request.
     */

    /**
     * The referral URL to use when fetching the comic.
     *
     * @var string
     */
    var $referer = null;

    /**
     * The user-agent to use when fetching the comic.
     *
     * @var string
     */
    var $agent = null;

    /**
     * The username to use when fetching the comic.
     *
     * @var string
     */
    var $user = null;

    /**
     * The password to use when fetching the comic.
     *
     * @var string
     */
    var $pass = null;

    /**
     * Cookies to set when fetching the comic.
     *
     * @var array
     */
    var $cookies = array();

    /**
     * Headers to set when fetching the comic.
     *
     * @var array
     */
    var $headers = array();

    /**
     * An array of the fields we need to do substitution on.
     *
     * @var array
     */
    var $subs = null;

    //
    // used for the {i} construct (for sites that id comics by "instance")
    //

    /**
     * Method for counting instances (when using the 'i' construct in
     * substitutions.
     *
     * @var string
     */
    var $itype = null;

    /**
     * Format string for the instance construct (printf string)
     *
     * @var string
     */
    var $iformat = '%d';

    /**
     * The number of the "first" instance of the comic (the reference
     * number) when using the reference-based instance type
     *
     * @var integer
     */
    var $icount = 0;

    /**
     * The date for which the reference is icount.
     *
     * @var date
     */
    var $idate = null;

    /**
     * The day the "week" starts for instance type weekly.
     * Abbreviated day name in english, lowercase.
     *
     * @var string
     */
    var $isow = 'sun';

    /**
     * The array of overrides by weekday.  If sun_url exists, then
     * when trying to fetch the sunday edition of this comic, it will
     * fetch it from the specified url instead of $url.
     *
     * @var array
     */
    var $override = array();

    /**
     * Loads the $comics[$comic] array into this object
     *
     * @param string $comic  The comic to create this object from
     */
    function Klutz_Comic(&$comic)
    {
        // what variables should we try to set directly from the comic array?
        $vars = array('name', 'author', 'homepage', 'comment', 'offset',
                      'url', 'itype', 'iformat', 'icount', 'idate', 'isow',
                      'referer', 'agent', 'user', 'pass', 'nohistory');

        // set the variables for this object that we've been passed
        foreach ($vars as $field) {
            if (!empty($comic[$field]) && !is_array($comic[$field])) {
                $this->$field = $comic[$field];
                unset($comic[$field]);
            }
        }

        if (!is_null($this->idate) && !is_numeric($this->idate)) {
            $this->idate = strtotime($this->idate);
        }

        // What arrays should we try to set from the comic array, and
        // do we want to perform a function?
        $arrays = array('days' => 'strtolower', 'headers' => null,
                        'subs' => 'strtolower', 'cookies' => null);

        // set the arrays - make sure each is an array & values lowercased!
        foreach ($arrays as $field => $function) {
            if (isset($comic[$field]) && is_array($comic[$field])) {
                if (is_null($function)) {
                    $this->$field = $comic[$field];
                } else {
                    $this->$field = array_map($function, $comic[$field]);
                }
                unset($comic[$field]);
            }
        }

        // Set any override strings in $this->override[]. Capitalize
        // and shorten the day keys to match date('D').
        if (isset($comic['override']) && is_array($comic['override']) && count($comic['override'])) {
            foreach ($comic['override'] as $dow => $value) {
                if (strlen($dow) >= 3) {
                    $this->override[ucfirst(substr($dow,0,3))] = $value;
                }
            }

        }

        // Anything left should be specific to the fetch driver.
        // Let the derivative class handle any extra parsing
    }

    /**
     * Create an HTTP_Request object and set all parameters necessary to
     * perform fetches for this comic.
     *
     * @param timestamp $date  Date of the comic to retrieve (default today)
     */
    function _initHTTP($date, $url)
    {
        if (is_null($this->http)) {
            $options = array();
            if (isset($GLOBALS['conf']['http']['proxy']) && !empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
                $options = array_merge($options, $GLOBALS['conf']['http']['proxy']);
            }

            require_once 'HTTP/Request.php';
            $this->http = new HTTP_Request($url, $options);

            $v = $this->getOverride("referer", $date);
            if (!is_null($v)) {
                $this->http->addHeader('Referer', $v);
            }

            $v = $this->getOverride("agent");
            if (!is_null($v)) {
                $this->http->addHeader('User-Agent', $v);
            }

            $user = $this->getOverride("user", $date);
            $pass = $this->getOverride("pass", $date);
            if (!is_null($user) and !is_null($pass)) {
                $this->http->setBasicAuth($user, $pass);
            }

            foreach ($this->getOverride('cookies', $date) as $name => $value) {
                $this->http->addCookie($name, $value);
            }
            foreach ($this->getOverride('headers', $date) as $name => $value) {
                $this->addHeader($name, $value);
            }
        }
    }

    /**
     * Turn the search strings from the configuration file into
     * preg_match-formatted strings
     *
     * @param array $comic  An array containing the content portion of Perl
     *                      regular expressions
     *
     * @return array  Search strings properly formatted to be used with
     *                preg_match
     *
     * @access private
     */
    function _prepareSearch($search)
    {
        foreach (array_keys($search) as $i) {
            if (is_array($search[$i])) {
                $search[$i] = $this->_prepareSearch($search[$i]);
            } elseif (substr($search[$i], 0, 1) != '|') {
                $search[$i] = '|' . $search[$i] . '|i';
            }
        }
        return $search;
    }

    /**
     * Check for "override" settings - settings that override other
     * settings depending on the day on which the comic appears
     *
     * @param string $setting    The name of the setting to override
     * @param timestamp $date    The date to check for overrides
     * @param string $array_map  Filter to be used with array_map
     *
     * @return mixed  If the setting is an array, returns the setting passed
     *                through array_map if array_map was passed.  Otherwise,
     *                returns the value of the setting, overridden if an
     *                override is present
     */
    function getOverride($setting, $date = null, $array_map = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        $day = date('D', $date);

        if (isset($this->override[$day][$setting])) {
            if ((isset($this->$setting) && is_array($this->$setting)) ||
                !is_null($array_map)) {
                if (is_array($this->$setting)) {
                    return array_map($array_map,
                                     array($this->override[$day][$setting]));
                } else {
                    return array_map($array_map, $this->override[$day][$setting]);
                }
            } else {
                return $this->override[$day][$setting];
            }
        } else {
            return $this->$setting;
        }
    }

    /**
     * Process known substitutions in a string.  Currently known options:<br />
     * o {dow(int day, string format)} day is numeric day of the week, format
     *   format is an strftime string (e.g. '%Y%m%d'), replaced with the
     *   formatted date for the requested day of the week
     * o {i} replaced with the instance of this comic as determined by
     *   the various instance configuration options<br />
     * o {format} format is an strftime string, replaced with todays date
     *   formatted according to the format string<br />
     * o {lc(string)} replaces string with string lowercased<br />
     * o {uc(string)} replaces string with string uppercased<br />
     * o {t(string)} removes extra space surrounding string<br />
     * o {tl0(string)} removes leading zeroes from string
     *
     * @param string $string   String to process
     * @param timestamp $date  Date to use when processing subs
     *
     * @return string  A string with all substitutions made
     */
    function substitute($string, $date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }
        $d = getdate($date);

        if (is_array($string)) {
            foreach (array_keys($string) as $i) {
                $string[$i] = $this->substitute($string[$i], $date);
            }
            return $string;
        }

        while (preg_match('/\{dow\((\d)\,\s*(.*?)\)\}/ie',$string,$dow) > 0) {
            $s = strftime($dow[2], mktime(0, 0, 0, $d['mon'],
                                          $d['mday'] - ($d['wday'] - $dow[1]),
                                          $d['year']));
//            $s = strftime($dow[2], $date+3600-(86400*($d['wday'] - $dow[1])));
            $string = str_replace($dow[0], $s, $string);
        }
        $string = preg_replace('/\{i\}/i',
                               $this->getInstance($date),
                               $string);
        $string = preg_replace('/(?<![\134]\w)(\{[^\}]+\})/e',"strftime('\\1', $date)", $string);
        $string = preg_replace('/\{lc\((.*?)\)\}/ie',"strtolower('\\1')", $string);
        $string = preg_replace('/\{uc\((.*?)\)\}/ie',"strtoupper('\\1')", $string);
        $string = preg_replace('/\{t\((.*?)\)\}/ie',"trim('\\1')", $string);
        $string = preg_replace('/\{tl0\((.*?)\)\}/ie',"ltrim('\\1','0')", $string);
        $string = preg_replace('/(?<![\134]\w)\{(.*?)\}/', "\\1\\2", $string);

        return $string;
    }

    /**
     * Get the instance requested based on the date.  The instance is
     * determined by itype, iformat, idate, isow
     *
     * @param timestamp $date           The date the instance occurs on
     *
     * @return string                   An strftime-formatted string based on
     *                                  the iformat parameter
     */
    function getInstance($date)
    {
        $itype = $this->getOverride('itype', $date);
        $iformat = $this->getOverride('iformat', $date);

        // get an instance if needed
        $method = 'getInstance_' . $itype;
        if (method_exists($this, $method)) {
            $instance = $this->$method($date);
        } else {
            $instance = '';
        }

        return sprintf($iformat, $instance);
    }

    /**
     * Get an instance number for a comic that appears monthly
     *
     * @param timestamp $date  The date the comic appears
     *
     * @return integer  The instance number (unformatted)
     */
    function getInstance_monthly($date)
    {
        // get the timestamp for the first day of the month and
        // make sure time for $date is midnight
        $d = getdate($date);
        $date = mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);
        $d = mktime(0, 0, 0, $d['mon'], 1, $d['year']);

        $days = $this->getOverride('days', $date, 'strtolower');

        // figure out how many times the comic should have appeared this month
        $instance = 0;
        while ($d <= $date) {
            $dow = getdate($d);
            $dow = substr(Horde_String::lower($d['weekday']),0,3);
            if (in_array($dow, $days)) {
                $instance++;
            }
            $d = mktime(0, 0, 0, $dow['mon'], $dow['mday'] + 1, $dow['year']);
        }

        return $instance;
    }

    /**
     * Get an instance number for a comic that appears weekly
     *
     * @param timestamp $date           The date the comic appears
     *
     * @return integer                  The instance number (unformatted)
     */
    function getInstance_weekly($date)
    {
        return '';
    }

    /**
     * Get an instance number for a comic that appears yearly (NOT IMPLEMENTED!)
     *
     * @param timestamp $date           The date the comic appears
     *
     * @return integer                  The instance number (unformatted)
     */
    function getInstance_yearly($date)
    {
        return '';
    }

    /**
     * Get an instance number for a comic based on a date reference.
     * This takes the idate option as a reference date, then uses the
     * 'days' setting to determine how often it appears.  Using this
     * information it extrapolates which instance will occur on the
     * date requested.
     *
     * @param timestamp $date           The date the comic appears
     *
     * @return integer                  The instance number (unformatted)
     */
    function getInstance_ref($date)
    {
        $d = $this->getOverride('idate', $date);
        $c = $this->getOverride('icount', $date);
        $days = $this->getOverride('days', $date);

        if ($d < $date) {
            // The reference date is older than the requested date

            // how many full weeks can we jump?
            $j = floor(($date - $d)/604800);
            $c += $j * count($days);
            $d += $j * 604800;
            while ($d <= $date) {
                $d = getdate($d);
                $d = mktime(0, 0, 0, $d['mon'], $d['mday'] + 1, $d['year']);
                if (in_array(Horde_String::lower(date('D', $d)), $days)) {
                    $c++;
                }
            }
        } else {
            // The reference date is newer than the requested date

            // how many full weeks can we jump?
            $j = floor(($d - $date)/604800);
            $c -= $j * count($days);
            $date += $j * 604800;
            while ($date < $d) {
                $d = getdate($d);
                $d = mktime(0, 0, 0, $d['mon'], $d['mday'] - 1, $d['year']);
                if (in_array(Horde_String::lower(date('D', $d)), $days)) {
                    $c--;
                }
            }
        }

        return $c;
    }
}
