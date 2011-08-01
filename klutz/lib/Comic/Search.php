<?php
/**
 * Klutz_Comic_Search Class.
 *
 * This class takes uses $comic->url to get an image searchly.
 * This is, of course, the most efficient as it takes one fetch.
 *
 * @author Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz_Comic_Search extends Klutz_Comic
{
    /**
     * Once set, an array of preg searches to perform to find the comic image
     *
     * @var array
     */
    var $search = null;

    /**
     * Constructor - Create an object that can be used to retrieve a comic
     * by using a preg_match to reliably get the comic URL from an html
     * document.
     *
     * @param string $comic  Index for the comic
     */
    function Klutz_Comic_search($comic)
    {
        // call the parent constructor...this should leave $comic with just
        // the parameters we need for fetching (if any are left)

        $par = get_parent_class($this);
        $this->$par($comic);

        if (is_null($this->subs)) {
            $this->subs = array('url');
        }

        // Hopefully we have at least one search pattern
        if (is_array($comic['search']) && count($comic['search']) > 0) {
            $this->search = $comic['search'];
            unset($comic['search']);
        } elseif (is_string($comic['search']) && !empty($comic['search'])) {
            $this->search = array($comic['search']);
            unset($comic['search']);
        } else {
            return null;
        }

        $this->search = $this->_prepareSearch($this->search);
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

        $this->_initHTTP($date, $url);

        // loop through the array of searches to get a final URL
        foreach ($this->getOverride('search', $date,
                                    array($this, '_prepareSearch')) as $search) {
            if (in_array('search', $this->getOverride('subs', $date))) {
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
            if (!isset($matches[1]) && $this->days != 'random') {
                $msg  = "URL: $url";
                $msg .= "\nSEARCH: " . print_r($search, true);
                $msg .= "\nHTML: " . $this->http->getResponseBody();
                Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
                return false;
            }

            if (strstr($matches[1], '://')) {
                $url = $matches[1];
            } elseif ($matches[1][0] == '/') {
                $url = preg_replace("|^(http://.*?)/.*$|", '\\1', $url);
                $url .= $matches[1];
            } else {
                $url = preg_replace("|^(http://[^?]*/).*$|", '\\1', $url);
                $url .= $matches[1];
            }
        }

        return $url;
    }

    /**
     * Fetch the actual image.
     *
     * @param timestamp $date  The date to retrieve the comic for (default
     *                         today).
     *
     * @return mixed  Klutz_Image on success, false otherwise.
     */
    function &fetchImage($date = null)
    {
        $url = $this->fetchURL($date);
        if ($url === false) {
            $false = false;
            return $false;
        }

        $this->_initHTTP($date, $url);
        $this->http->setURL($url);
        $this->http->sendRequest();

        $image = &new Klutz_Image($this->http->getResponseBody());
        if (is_null($image) || is_null($image->type)) {
            $image = false;
        }

        return $image;
    }

}
