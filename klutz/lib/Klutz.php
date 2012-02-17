<?php

/**
 * Sort by the comic's name.
 */
define('KLUTZ_SORT_NAME', 201);

/**
 * Sort by the comic's author.
 */
define('KLUTZ_SORT_AUTHOR', 202);

/**
 * Don't bother sorting.
 */
define('KLUTZ_SORT_NOSORT', 203);

/**
 * Width (integer).
 */
define('KLUTZ_FLD_WIDTH', 0);

/**
 * Height (integer).
 */
define('KLUTZ_FLD_HEIGHT', 1);

/**
 * Type (integer).
 */
define('KLUTZ_FLD_TYPE', 2);

/**
 * IMG-appropriate HTML.
 */
define('KLUTZ_FLD_HTML', 3);

/**
 * Klutz Base Class.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz
{
    /**
     * Used to translate between literal dow and numeric dow (sun = 0, 7)
     *
     * @var array
     */
    var $days = array('sun', 'mon', 'tue', 'wed',
                      'thu', 'fri', 'sat', 'sun');

    /**
     * MIME types for images based on the return value from getimagesize
     *
     * @var array
     */
    var $image_types = array(1 => 'image/gif',
                             2 => 'image/jpg',
                             3 => 'image/png',
                             6 => 'image/bmp',
                             7 => 'image/tiff',
                             8 => 'image/tiff',
                             );

    /**
     * Array of comics and their associated attributes
     *
     * @var array
     */
    var $comics = array();

    /**
     * Sorting method to use.  Options are KLUTZ_SORT_NAME (sort by comic
     * title), KLUTZ_SORT_AUTHOR (sort by Author's last name), and
     * KLUTZ_SORT_NOSORT (don't promise any given sorting order).
     *
     * @var integer
     */
    var $sort = null;

    /**
     * Constructor - Parse the /config/comics.php config file and store
     * the results in $comic.  Also tries to validate all the data it can
     * and adjust case, etc., to more predictible consistency than humans
     * editing config files can give. :)
     *
     * @param integer $sort         Sorting method to use
     */
    function Klutz($sort = KLUTZ_SORT_NAME)
    {
        $this->sort = $sort;

        // Load the list of comics from the config file.
        include_once KLUTZ_BASE . '/config/comics.php';

        if (isset($comics)) {
            $this->comics = $comics;
        }

        if ($this->sort != KLUTZ_SORT_NOSORT) {
            uksort($this->comics, array($this, '_sortComics'));
        }

        foreach (array_keys($this->comics) as $index) {
            if (empty($this->comics[$index]['days'])) {
                $this->comics[$index]['days'] = array_unique($this->days);
            } else {
                if (!is_array($this->comics[$index]['days'])) {
                    if (Horde_String::lower($this->comics[$index]['days']) == 'random') {
                        $this->comics[$index]['days'] = 'random';
                    } else {
                        $this->comics[$index]['days'] =
                         array($this->comics[$index]['days']);
                    }
                }

                if (is_array($this->comics[$index]['days'])) {
                    $this->comics[$index]['days'] = array_map(array($this, '_convertDay'),
                                                              $this->comics[$index]['days']);
                }
            }
            if (empty($this->comics[$index]['nohistory'])) {
                $this->comics[$index]['nohistory'] = false;
            }
        }
    }

    /**
     * Convert a user-passed string into a three-letter, lowercased day abbr.
     *
     * @param string $a             The string to convert
     *
     * @return string               A three-letter abbreviation for the
     *                              requested day, or the first three letters
     *                              of the passed string lowercased.
     */
    function _convertDay($a)
    {
        $a = Horde_String::lower(substr($a,0,3));
        if (!in_array($a, $this->days)) {
            switch ($a) {
            case 'm':
            case 'mo':
                $a = 'mon';
                break;

            case 't':
            case 'tu':
                $a = 'tue';
                break;

            case 'w':
            case 'we':
                $a = 'wed';
                break;

            case 'r':
            case 'th':
                $a = 'thu';
                break;

            case 'f':
            case 'fr':
                $a = 'fri';
                break;

            case 's':
            case 'su':
                $a = 'sat';
                break;

            case 'u':
            case 'su':
                $a = 'sun';
                break;
            }
        }

        return $a;
    }

    /**
     * Comparse two comics and return -1, 0, or 1 based on the $sort member
     * variable
     *
     * @param string $a                 The index of the first comic
     * @param string $b                 The index of the second comic
     *
     * @return integer                  @see strcmp
     */
    function _sortComics($a, $b)
    {
        switch ($this->sort) {
        case KLUTZ_SORT_NAME:
            $namea = preg_replace("/^(A|An|The)\s+/i", '', $this->comics[$a]['name']);
            $nameb = preg_replace("/^(A|An|The)\s+/i", '', $this->comics[$b]['name']);

            return strcmp($namea, $nameb);

        case KLUTZ_SORT_AUTHOR:
            $authora = preg_replace("/^.*?\s+/", '', $this->comics[$a]['author']);
            $authorb = preg_replace("/^.*?\s+/", '', $this->comics[$b]['author']);
            $authora = preg_replace("/^\s*and\s+.*?\s+/", '', $authora);
            $authorb = preg_replace("/^\s*and\s+.*?\s+/", '', $authorb);

            return strcmp($authora, $authorb);
        }
    }

    /**
     * Return a list of comics that are marked as enabled, and that should
     * appear on a given day if a date is passed in.
     *
     * @param array $list      The list to extract from (default is all comics,
     *                         but smaller arrays are okay).
     * @param timestamp $date  If passed in, check the $days array to make sure
     *                         the comic should appear today.
     *
     * @return array  A list of comic indecies
     */
    function listEnabled($list = null, $date = null)
    {
        if (is_null($list)) {
            $list = array_keys($this->comics);
        }

        $day = null;
        if (!is_null($date)) {
            $day = Horde_String::lower(date('D', $date));
        }

        $return = array();
        foreach ($list as $index) {
            if (!isset($this->comics[$index])) continue;
            if ($this->comics[$index]['enabled']) {
                if (is_null($day) || $this->comics[$index]['days'] == 'random'
                    || in_array($day, $this->comics[$index]['days'])) {
                    $return[] = $index;
                }
            }
        }
        return $return;
    }

    /**
     * Return a list of comics that use the given fetch driver
     *
     * @param string $driver  The driver to check for
     * @param array $list     The list to filter by driver (default all comics)
     *
     * @return array  A list of the comics passed in that have been filtered
     *                based on driver
     */
    function listByDriver($driver, $list = null)
    {
        if (is_null($list)) { $list = array_keys($this->comics); }

        $return = array();
        foreach ($list as $index) {
            if ($this->comics[$index]['method'] == $driver) {
                $return[] = $index;
            }
        }
        return $return;
    }

    /**
     * Return a Klutz_Comic for the given index
     *
     * @param string $index             The index key for the desired comic
     *
     * @return object                   A Klutz_Comic object for the given index
     */
    function comicObject($index)
    {
        if (empty($this->comics[$index])) {
            return null;
        }

        $driver = ucfirst($this->comics[$index]['method']);
        $class = 'Klutz_Comic_' . $driver;
        if (class_exists($class)) {
            return new $class($this->comics[$index]);
        } else {
            return null;
        }
    }

    /**
     * Return the requested property for the requested comic
     *
     * @param string $index             The index key for the desired comic
     * @param string $property          The desired property
     *
     * @return mixed                    The value of $property for $index
     */
    function getProperty($index, $property)
    {
        if (!is_array($index) && is_array($property)) {
            $return = array();
            foreach ($property as $p) {
                $return[$p] = $this->comics[$index][$p];
            }
            return $return;
        }

        if (is_array($index)) {
            $return = array();
            foreach ($index as $i) {
                $return[$i] = $this->getProperty($i, $property);
            }
            return $return;
        }

        if (isset($this->comics[$index][$property])) {
            return $this->comics[$index][$property];
        } else {
            return null;
        }
    }
}
