<?php
/**
 * Klutz Driver implementation for comics as files.
 *
 * Required parameters:<pre>
 *   'directory'  The main directory the comics are stored in
 *   'sumsfile'   The filename to hold md5sums for images</pre>
 *
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @since   Klutz 0.1
 * @package Klutz
 */
class Klutz_Driver_File extends Klutz_Driver
{
    /**
     * The base directory we store comics in.
     *
     * @var string
     */
    var $basedir = null;

    /**
     * The format for the various subdirectories.
     * WARNING: DO NOT CHANGE THIS!
     *
     * @var string
     */
    var $subdir = 'Ymd';

    /**
     * The file we store unique image identifiers in.
     *
     * @var string
     */
    var $sumsfile = 'sums';

    /**
     * The actual array of unique image identifiers (md5 sums right now).
     *
     * Key is the full path of the comic, value is md5
     *
     * @var array
     */
    var $diffs = array();

    /**
     * Constructs a new file storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Klutz_Driver_file($params = array())
    {
        if (empty($params['basedir'])) {
            return null;
        }

        $this->basedir = $params['basedir'];
        if (substr($this->basedir,-1,1) != "/") {
            $this->basedir .= "/";
        }

        if (!empty($params['sumsfile'])) {
            $this->sumsfile = $params['sumsfile'];
        }
        if (substr($this->sumsfile, 0, 1) != "/") {
            $this->sumsfile = $this->basedir . $this->sumsfile;
        }

        $this->loadSums();
    }

    /**
     * Load a list of unique identifiers for comics from the sumsfile.
     *
     * @return void
     */
    function loadSums()
    {
        if (file_exists($this->sumsfile)) {
            ob_start();
            readfile($this->sumsfile);
            foreach (explode("\n", ob_get_contents()) as $entry) {
                if (empty($entry)) {
                    continue;
                }
                list($file, $sum) = explode(') = ', $entry);
                $this->diffs[substr($file, 5)] = $sum;
            }
            ob_end_clean();
        }
    }

    /**
     * Save the list of unique identifiers for comics to the sumsfile.
     *
     * @return void
     */
    function saveSums()
    {
        $fp = fopen($this->sumsfile, "wb+");
        if ($fp === false) {
            Horde::logMessage('Unable to create/write to ' . $this->sumsfile,
                              __FILE__, __LINE__, PEAR_LOG_ERR);
            return false;
        }
        foreach ($this->diffs as $file => $sum) {
            if (empty($file)) { continue; }
            fwrite($fp, "MD5 ($file) = $sum\n");
        }
        fclose($fp);
    }

    /**
     * Rebuild the table of unique identifiers.
     *
     * @return void
     */
    function rebuildSums()
    {
        $this->diffs = array();
        $d = dir($this->basedir);
        while (false !== ($entry = $d->read())) {
            if (is_dir($d->path . $entry) && strlen($entry) == 8
                && is_numeric($entry)) {

                // we're reasonably sure this is a valid dir
                $sd = dir($this->basedir . $entry);
                while (false !== ($file = $sd->read())) {
                    $file = $sd->path . '/' . $file;
                    if (is_file($file)) {
                        ob_start();
                        readfile($file);
                        $this->diffs[$file] = md5(ob_get_contents());
                        ob_end_clean();
                    }
                }
            }
        }
        $d->close();
        $this->saveSums();
    }

    /**
     * Add a unique identifier for a given image.
     *
     * @param string $index             The index for the comic
     * @param timestamp $date           The date of the comic
     * @param string $data              The md5 of the raw (binary) image data
     *
     * @return void
     */
    function addSum($index, $date, $data)
    {
        $key = $this->basedir . date($this->subdir, $date) . '/' . $index;
        $this->diffs[$key] = $data;
    }

    /**
     * Remove the unique identifier for the given comic and/or
     * date. If both are passed, removes the uid for that comic and
     * date. If only a comic is passed, removes all uids for that
     * comic. If only a date is passed removes uids for all comics on
     * that date. If neither is passed, all uids are wiped out.
     *
     * @param string $index    Index for the comic to delete.  If left out all
     *                         comics will be assumed.
     * @param timestamp $date  Date to remove. If left out, assumes all dates.
     *
     * @return void
     */
    function removeSum($index = null, $date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        if (is_null($index)) {
            $cmp = $this->basedir . date($this->subdir, $date);
            foreach (array_keys($this->diffs) as $key) {
                if (strncmp($key, $cmp, strlen($cmp)) == 0) {
                    unset($this->diffs[$key]);
                }
            }
        } else {
            $key = $this->basedir . date($this->subdir, $date) . '/' . $index;
            unset($this->diffs[$key]);
        }
    }

    /**
     * Determine if the image passed is a unique image (one we don't already
     * have).
     *
     * This allows for $days = random, etc., but keeps us from getting the same
     * comic day after day.
     *
     * @param object Klutz_Image $image  Raw (binary) image data
     *
     * @return boolean  True if unique, false otherwise.
     */
    function isUnique($image)
    {
        if (!is_a($image, 'Klutz_Image')) {
            return null;
        }

        return !in_array(md5($image->data), $this->diffs);
    }

    /**
     * Get a list of the dates for which we have comics between
     * $oldest and $newest. Only returns dates we have at least one
     * comic for.
     *
     * @param timestamp $date    The reference date (default today)
     * @param timestamp $oldest  The earliest possible date to return (default
     *                           first of the month)
     * @param timestamp $newest  The latest possible date to return (default
     *                           last date of the month)
     *
     * @return array timestamps  Any dates between $oldest and $newest that we
     *                           have comics for.
     */
    function listDates($date = null, $oldest = null, $newest = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        // Using Date as a reference, return all dates for the same
        // time of day.
        $d = getdate($date);

        if (is_null($oldest)) {
            $oldest = mktime(0, 0, 0, $d['mon'], 1, $d['year']);
        }

        if (is_null($newest)) {
            $newest = mktime(0, 0, 0, $d['mon'] + 1, 0, $d['year']);
        }

        $d = @dir($this->basedir);
        if (!$d) {
            return array();
        }

        $return = array();
        while (false !== ($entry = $d->read())) {
            if (is_dir($d->path . $entry) && strlen($entry) == 8
                && is_numeric($entry)) {

                // we're reasonably sure this is a valid dir
                $time = mktime(0, 0, 0, substr($entry, 4, 2),
                               substr($entry, -2), substr($entry, 0, 4));
                if ($time >= $oldest && $time <= $newest) {
                    $return[] = $time;
                }
            }
        }
        $d->close();
        sort($return, SORT_NUMERIC);
        return $return;
    }

    /**
     * Get the image dimensions for the requested image.
     *
     * @param string $index    The index of the comic to check
     * @param timestamp $date  The date of the comic to check (default today)
     *
     * @return string  Attributes for an <img> tag giving height and width
     */
    function imageSize($index, $date = null)
    {
        $image = $this->retrieveImage($index, $date);
        if (is_a($image, 'Klutz_Image') && !empty($image->size)) {
            return $image->size;
        } else {
            return '';
        }
    }

    /**
     * Find out if we already have a local copy of this image.
     *
     * @param string $index    The index of the comic to check
     * @param timestamp $date  The date of the comic to check (default today)
     *
     * @return boolean  False in this driver
     */
    function imageExists($index, $date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        $dir = $this->basedir . date($this->subdir, $date);
        return (file_exists($dir . '/' . $index) && is_file($dir . '/' . $index));
    }

    /**
     * Retrieve an image from storage. Make sure the image exists
     * first with imageExists().
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

        $dir = $this->basedir . date($this->subdir, $date);
        return new Klutz_Image($dir . '/' . $index);
    }

    /**
     * Store an image for later retrieval.
     *
     * @param string $index    The index of the comic to retrieve
     * @param string $image    Raw (binary) image data to store
     * @param timestamp $data  Date to store it under (default today)
     *
     * @return boolean  True on success, false otherwise
     */
    function storeImage($index, $image, $date = null)
    {
        if (!is_a($image, 'Klutz_Image')) {
            return false;
        }

        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        // Make sure $this->basedir exists and is writeable.
        if (!file_exists($this->basedir)) {
            if (!file_exists(dirname($this->basedir))) {
                return false;
            }
            if (!mkdir($this->basedir)) {
                return false;
            }
        }
        if (!is_writable($this->basedir)) {
            return false;
        }

        $dir = $this->basedir . date($this->subdir, $date);

        if (!file_exists($dir)) {
            mkdir($dir);
        } elseif (!is_writable($dir)) {
            return false;
        }

        $fp = fopen($dir . '/' . $index, 'w+');
        fwrite($fp, $image->data);
        fclose($fp);
        $this->addSum($index, $date, md5($image->data));
        return true;
    }

    /**
     * Remove an image from the storage system (including its unique
     * ID).
     *
     * @param string $index    The index of the comic to remove
     * @param timestamp $date  The date of the comic to remove (default today)
     *
     * @return boolean  True on success, else false
     */
    function removeImage($index, $date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        if ($this->imageExists($index, $date)) {
            $file = $this->basedir . date($this->subdir, $date) . '/' . $index;
            if (unlink($file)) {
                $this->removeSum($index, $date);
                return true;
            }
        }
        return false;
    }

    /**
     * Remove all images from the storage system (including unique
     * IDs) for a given date.
     *
     * @param timestamp $date  The date to remove comics for (default today)
     *
     * @return boolean  True on success, else false
     */
    function removeDate($date = null)
    {
        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        $dir = $this->basedir . date($this->subdir, $date);
        if (file_exists($dir) && is_dir($dir)) {
            $d = dir($dir);
            if (!$d) {
                return false;
            }

            while (false !== ($file = $d->read())) {
                $file = $d->path . '/' . $file;
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $d->close();
            if (@rmdir($dir)) {
                $this->removeSum(null, $date);
                return true;
            }
        }

        return false;
    }

}
