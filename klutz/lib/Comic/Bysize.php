<?php
/**
 * Klutz_Comic_Bysize Class.
 *
 * This class uses follows the search methodology until it hits the
 * final page in the list.  On this page it gets a list of all images
 * and tries to figure out which image is most likely to be the comic
 * based on image sizes.  This is the LEAST efficient driver and
 * you're discouraged from using it when not absolutely necessary.
 *
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz_Comic_Bysize extends Klutz_Comic
{
    /**
     * Once set, an array of preg searches to perform to find the comic image
     *
     * @var array
     */
    var $search = null;

    /**
     * A list of images to ignore (preg matches)
     *
     * @var array
     */
    var $ignore = array();

    /**
     * What is the smallest height to consider
     *
     * @var integer
     */
    var $minheight = 0;

    /**
     * What are the smallest width to consider
     *
     * @var integer
     */
    var $minwidth = 0;

    /**
     * What is the largest height to consider
     *
     * @var integer
     */
    var $maxheight = 65536;

    /**
     * What is the largest width to consider
     *
     * @var integer
     */
    var $maxwidth = 65536;

    /**
     * How should we decide which image to take? Options are "first",
     * "biggest", and "smallest".
     *
     * @param string
     */
    var $choose = 'biggest';

    /**
     * Constructor - Create an object that can be used to retrieve a comic
     * by looking at all images on a page, a list of images to ignore, and
     * a range of dimensions, then choose which image is most likely the
     * comic.
     *
     * @param string $comic                 Index for the comic
     */
    function Klutz_Comic_bysize($comic)
    {
        // call the parent constructor...this should leave $comic with just
        // the parameters we need for fetching (if any are left)

        $par = get_parent_class($this);
        $this->$par($comic);

        if (is_null($this->subs)) {
            $this->subs = array('url');
        }

        // Check to see if we have one search pattern
        if (empty($comic['search'])) {
            $this->search = array();
        } elseif (is_array($comic['search']) && count($comic['search']) > 0) {
            $this->search = $comic['search'];
            unset($comic['search']);
        } elseif (is_string($comic['search']) && !empty($comic['search'])) {
            $this->search = array($comic['search']);
            unset($comic['search']);
        } else {
            $this->search = array();
        }

        $this->search = $this->_prepareSearch($this->search);

        // Check to see if we have any ignores
        if (isset($comic['ignore'])) {
            if (is_array($comic['ignore']) && count($comic['ignore']) > 0) {
                $this->ignore = $comic['ignore'];
            } elseif (is_string($comic['ignore']) && !empty($comic['ignore'])) {
                $this->ignore = array($comic['ignore']);
            }
            unset($comic['ignore']);
        }

        foreach (array('minheight', 'maxheight', 'minwidth', 'maxwidth') as $f) {
            if (!empty($comic[$f])) {
                $this->$f = $comic[$f];
            }
            unset($comic[$f]);
        }

        if (isset($comic['choose'])) {
            $this->choose = $comic['choose'];
            unset($comic[$f]);
        }
    }

    /**
     * Do all that is necessary to get the final URL from which the comic
     * will be fetched.  Instead of returning the comic, return the URL
     * pointing to that comic.
     *
     * @param timestamp $date  Date of the comic to retrieve (default today)
     *
     * @return string  URL of the comic image
     */
    function fetchURL($date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }
        $offset = $this->getOverride('offset', $date);
        $d = getdate($date);
        $date = mktime(0, 0, 0, $d['mon'], $d['mday'] - $offset, $d['year']);

        $url = $this->getOverride('url', $date);
        if (in_array('url', $this->getOverride('subs', $date))) {
            $url = $this->substitute($url, $date);
        }

        // make sure $this->http is set up properly
        $this->_initHTTP($date, $url);

        // loop through the array of searches to get a final URL
        foreach ($this->getOverride('search', $date,
                                    array($this, '_prepareSearch')) as $search) {
            if (in_array('search', $this->subs)) {
                $search = $this->substitute($search, $date);
            }

            $this->http->setURL($url);
            $this->http->sendRequest();
            if (is_array($search)) {
                $text = $this->http->getResponseBody();
                foreach ($search as $s) {
                    $num_matches = preg_match($s, $text, $matches);
                    if (isset($matches[1])) {
                        $text = $matches[1];
                    } elseif ($num_matches > 0) {
                        $text = $matches[0];
                    } else {
                        break;
                    }
                }
            } else {
                preg_match($search, $this->http->getResponseBody(), $matches);
            }

            if (empty($matches[1]) && $this->days != 'random') {
                $msg = "URL: $url" .
                    "\nSEARCH: " . print_r($search, true) .
                    "\nHTML: " . $this->http->getResponseBody();
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                return false;
            }

            if (strstr($matches[1], '://')) {
                $url = $matches[1];
            } elseif (substr($matches[1],0,1) == '/') {
                $url = preg_replace("|^(http://.*?)/.*$|", '\\1', $url);
                $url .= $matches[1];
            } else {
                $url = preg_replace("|^(http://[^?]*/).*$|", '\\1', $url);
                $url .= $matches[1];
            }
        }

        // At this point we should have a URL we need to get the list of
        // images from.
        $this->http->setURL($url);
        $this->http->sendRequest();
        $images = $this->_stripimages($this->http->getResponseBody());
        $images = $this->_expandurls($images, $url);
        $images = $this->_getsizes($images, true, $date);

        // make sure we actually have a list of images to work from
        if (count($images) == 0) { return false; }

        // if we have only one image it is the biggest, smalles, first...
        if (count($images) == 1) { return $images[0]['url']; }

        switch ($this->getOverride('choose',$date)) {
        case 'biggest':
            $image = false;
            $max = 0;
            foreach ($images as $i) {
                $s = $i['height'] * $i['width'];
                if ( $s > $max) {
                    $max = $s;
                    $image = $i['url'];
                }
            }
            return $image;
            break;
        case 'smallest':
            $image = false;
            $min = 0;
            foreach ($images as $i) {
                $s = $i['height'] * $i['width'];
                if ( $s < $max) {
                    $min = $s;
                    $image = $i['url'];
                }
            }
            return $image;
            break;
        case 'first':
        default:
            return $images[0]['url'];
            break;
        }
    }

    /**
     * Fetch the actual image
     *
     * @param timestamp $date  The date to retrieve the comic for (default
     *                         today).
     *
     * @return mixed  Klutz_Image on success, false otherwise.
     */
    function &fetchImage($date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }
        $offset = $this->getOverride('offset', $date);
        $d = getdate($date);
        $date = mktime(0, 0, 0, $d['mon'], $d['mday'] - $offset, $d['year']);

        $url = $this->fetchURL($date);
        if ($url === false) {
            return $url;
        }

        // Make sure $this->http is set up properly.
        $this->_initHTTP($date, $url);

        $this->http->setURL($url);
        $this->http->sendRequest();

        $image = &new Klutz_Image($this->http->getResponseBody());
        if (is_null($image) || is_null($image->type)) {
            $image = false;
        }

        return $image;
    }

    /**
     * Strip the list of images from the contents of a web page.
     * Derived from Snoopy's striplinks function.
     *
     * @param string $document  The HTML document to parse.
     *
     * @return array  List of images in the page.
     */
    function _stripimages($document)
    {
        preg_match_all("'<\s*img.*?src\s*=\s*       # find <img src=
                        ([\"\'])?                   # find single or double quote
                        (?(1) (.*?)\\1 | ([^\s\>]+))# if quote found, match up to next matching
                                                    # quote, otherwise match up to next space
                        'isx", $document, $images);

        // Concatenate the non-empty matches from the conditional
        // subpattern.
        $match = array();
        foreach ($images[2] as $val) {
            if (!empty($val)) {
                $match[] = $val;
            }
        }
        foreach ($images[3] as $val) {
            if (!empty($val)) {
                $match[] = $val;
            }
        }

        // Return the images.
        return array_filter($match, array($this, '_ignore'));
    }

    /**
     * Expand paths to fully-qualified URLs
     *
     * @param array $urls   Paths to expand
     * @param string $base  The base URL used for relative links
     *
     * @return array  Fully-qualified URLs
     */
    function _expandurls($urls, $base)
    {
        $return = array();
        foreach ($urls as $url) {
            if (strstr($url, '://')) {
                // Don't do anything, but it saves some processing.
            } elseif (substr($url,0,1) == '/') {
                $url = preg_replace("|^(http://.*?)/.*$|", '\\1', $base) . $url;
            } else {
                $url = preg_replace("|^(http://.*/).*$|", '\\1', $base) . $url;
            }
            if (in_array(substr($url, 0, 4), array('http', 'ftp'))) {
                $return[] = $url;
            }
        }
        return $return;
    }

    /**
     * Determine if the passed image name is on the list of images to
     * ignore.
     *
     * @param string $string  The name to check
     *
     * @return boolean  True if we should ignore it
     */
    function _ignore($string)
    {
        foreach ($this->ignore as $ignore) {
            if (stristr($string, $ignore) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the dimensions from the list of images passed in.
     *
     * @param array $images    The list of images to check.
     * @param boolean $filter  Filter by size, etc? (true).
     * @param timestamp $date  Date to use for filter prefs.
     *
     * @return array  Dimensions for all desired images.
     */
    function _getsizes($images, $filter = true, $date = null)
    {
        $sizes = array();

        $minwidth = $this->getOverride('minwidth', $date);
        $minheight = $this->getOverride('minheight', $date);
        $maxwidth = $this->getOverride('maxwidth', $date);
        $maxheight = $this->getOverride('maxheight', $date);

        foreach ($images as $i) {
            $s = @getimagesize($i);
            if (!is_null($s)) {
                if ($filter) {
                    if ($s[KLUTZ_FLD_WIDTH]< $minwidth) {
                        continue;
                    }
                    if ($s[KLUTZ_FLD_HEIGHT] < $minheight) {
                        continue;
                    }
                    if ($s[KLUTZ_FLD_WIDTH] > $maxwidth) {
                        continue;
                    }
                    if ($s[KLUTZ_FLD_HEIGHT] > $maxheight) {
                        continue;
                    }
                }
                $sizes[] = array('url' => $i,
                                 'height' => $s[KLUTZ_FLD_HEIGHT],
                                 'width'  => $s[KLUTZ_FLD_WIDTH]);
            }
        }

        return $sizes;
    }

}
