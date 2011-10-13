<?php
/**
 * Klutz_Driver:: defines an API for storing and retrieving the comic images
 *
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @since   Klutz 0.1
 * @package Klutz
 */
class Klutz_Driver
{
    /**
     * Gets a concrete Klutz_Driver instance.
     *
     * @param string $driver  The type of concrete Klutz_Driver subclass to
     *                        return.  The code for the driver is dynamically
     *                        included.
     *
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need
     *
     * @return object Klutz_Driver  The newly created concrete instance, or
     *                              false on error.
     */
    function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['storage']['driver'];
        }
        $driver = ucfirst(basename($driver));

        if (is_null($params)) {
            $params = Horde::getDriverConfig('storage', $driver);
        }

        $class = 'Klutz_Driver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }
        return new Klutz_Driver($params);
    }

    /**
     * Gets a list of the dates for which we have comics between $oldest and
     * $newest.  In the default driver (no backend) this is just a list of
     * all dates between $oldest and $newest.
     *
     * @param timestamp $date    The reference date (default today)
     * @param timestamp $oldest  The earliest possible date to return (default
     *                           first of the month)
     * @param timestamp $newest  The latest possible date to return (default
     *                           last date of the month)
     *
     * @return array timestamps  Dates between $oldest and $newest we have
     *                           comics for
     */
    function listDates($date = null, $oldest = null, $newest = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        $dateparts = getdate($date);

        // Default to showing only the month specified.
        if (is_null($oldest)) {
            $oldest = mktime(0, 0, 0, $dateparts['mon'], 1, $dateparts['year']);
        }
        if (is_null($newest)) {
            $newest = mktime(0, 0, 0, $dateparts['mon'] + 1, 0, $dateparts['year']);
            $newest = min($newest, mktime(0, 0, 0));
        }

        $return = array();
        $i = date('j', $oldest);
        $loopMonth = date('n', $oldest);
        $loopYear = date('Y', $oldest);
        $loopStamp = mktime(0, 0, 0, $loopMonth, $i, $loopYear);
        while ($loopStamp <= $newest) {
            $return[] = $loopStamp;
            $loopStamp = mktime(0, 0, 0, $loopMonth, ++$i, $loopYear);
        }

        return $return;
    }

    /**
     * Get the image dimensions for the requested image.
     *
     * The image is not stored locally so this function returns an
     * empty string.  Performance hit is too expensive to make this
     * worth it.
     *
     * @param string $index    The index of the comic to check
     * @param timestamp $date  The date of the comic to check (default today)
     *
     * @return string  Attributes for an <img> tag giving height and width
     */
    function imageSize($index, $date = null)
    {
        // Getting the image size is too expensive for the benefit
        // when using this driver.
        return '';
    }

    /**
     * Find out if we already have a local copy of this image.
     *
     * Even though we never actually store a local copy, pretend.
     *
     * @param string $index    The index of the comic to check
     * @param timestamp $date  The date of the comic to check (default today)
     *
     * @return boolean  True
     */
    function imageExists($index, $date = null)
    {
        return true;
    }

    /**
     * Store an image for later retrieval
     *
     * Even though we never actually store a local copy, pretend.
     *
     * @param string $index    The index of the comic to retrieve
     * @param string $image    Raw (binary) image data to store
     * @param timestamp $data  Date to store it under (default today)
     *
     * @return boolean  True
     */
    function storeImage($index, $image, $date = null)
    {
        return true;
    }

    /**
     * Retrieve an image from storage.  Since there is no local storage
     * this will actually call for the fetching.
     *
     * @param string $index    The index of the comic to retrieve
     * @param timestamp $date  The date for which we want $comic
     *
     * @return mixed  If the image exists locally, return a Klutz_Image object.
     *                If it doesn't, return a string with the URL pointing to
     *                the comic.
     */
    function retrieveImage($index, $date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        // For this driver, we grab the image on the fly
        $comic = $GLOBALS['klutz']->comicObject($index);
        var_dump($comic);
        if (is_null($comic->referer) &&
            is_null($comic->agent) &&
            is_null($comic->user) &&
            is_null($comic->pass) &&
            count($comic->cookies) == 0 &&
            count($comic->headers) == 0) {
            return $comic->fetchURL($date);
        } else {
            return $comic->fetchImage($date);
        }
    }
}
