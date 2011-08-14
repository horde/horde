<?php
/**
 * Klutz_Comic_Direct Class.
 *
 * This class takes uses $comic->url to get an image directly.
 * This is, of course, the most efficient as it takes one fetch.
 *
 * @author Marcus I. Ryan <marcus@riboflavin.net>
 * @package Klutz
 */
class Klutz_Comic_Direct extends Klutz_Comic
{
    /**
     * Constructor - Create an object that can be used to retrieve a comic
     * directly from a URL passed in (using substitutions as necessary).
     *
     * @param string $comic  Index for the comic
     */
    function Klutz_Comic_direct($comic)
    {
        // Call the parent constructor...this should leave $comic with
        // just the parameters we need for fetching (if any are left).
        $par = get_parent_class($this);
        $this->$par($comic);

        if (is_null($this->subs)) {
            $this->subs = array("url");
        }

        // assuming $this->url is set, so are we...
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

        return $url;
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

        $url = $this->getOverride('url', $date);
        if (in_array('url', $this->getOverride('subs', $date))) {
            $url = $this->substitute($url, $date);
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
