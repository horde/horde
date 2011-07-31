<?php
require_once 'MDB2.php';

/**
 * Klutz Driver implementation for comics as files with SUM info stored
 * in SQL database.
 *
 * Required parameters:<pre>
 *   'directory'  The main directory the comics are stored in</pre>
 *
 * @author  Marcus I. Ryan <marcus@riboflavin.net>
 * @author  Florian Steinel <fsteinel@klutz.horde.flonet.net>
 * @package Klutz
 */
class Klutz_Driver_Sql extends Klutz_Driver
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
     * The MDB2 database object
     *
     * @var
     */
     var $_db = null;

    /**
     * Constructs a new SQL storage object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function Klutz_Driver_sql($params = array())
    {
        if (empty($params['basedir'])) {
            return null;
        }

        $this->basedir = $params['basedir'];
        if (substr($this->basedir, -1, 1) != "/") {
            $this->basedir .= "/";
        }

        /* Setup the database */
        $config = $GLOBALS['conf']['sql'];
        unset($config['charset']);
        $this->_db = MDB2::factory($config);
        $this->_db->setOption('seqcol_name', 'id');

    }

    /**
     * We do nothing in this function for the SQL driver since we grab
     * the info on demand from the database.  We keep the function here,
     * however to honor our 'interface' since we call this function from
     * various places in the client code.
     */
    function loadSums()
    {
    }

    /**
     * Rebuild the table of unique identifiers.
     *
     * @return void
     */
    function rebuildSums()
    {
        /* First, wipe out the existing SUMS */
        $this->removeSum();

        $d = dir($this->basedir);
        while (false !== ($entry = $d->read())) {
            if (is_dir($d->path . $entry) && strlen($entry) == 8
                && is_numeric($entry)) {
                // we're reasonably sure this is a valid dir.
                $sd = dir($this->basedir . $entry);
                while (false !== ($file = $sd->read())) {
                    $comicname = $file;
                    $file = $sd->path . '/' . $file;
                    if (is_file($file)) {
                        ob_start();
                        readfile($file);

                        // We need to strtotime() the date since we are grabing
                        // it from the directory, and it's in the $subdir
                        // format.
                        $this->addSum($comicname, strtotime($entry), md5(ob_get_contents()));
                        ob_end_clean();
                    }
                }
            }
        }
        $d->close();
    }

    /**
     * Add a unique identifier for a given image.
     *
     * @param string $index             The index for the comic
     * @param timestamp $date           The date of the comic
     * @param string $data              The md5 of the raw (binary) image data
     *
     * @return boolean|PEAR_Error       True on success, PEAR_Error on failure.
     */
    function addSum($index, $date, $data)
    {
        $id = $this->_db->nextId('klutz_comics');
        $key = $this->basedir . date($this->subdir, $date) . '/' . $index;

        /* Build the SQL query. */
        $query = $this->_db->prepare('INSERT INTO ' . 'klutz_comics (comicpic_id, comicpic_date, comicpic_key, comicpic_hash) VALUES (?, ?, ?, ?)');
        $values = array($id, $date, $key, $data);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf("Klutz_Driver_sql::addSum(): %s values: %s", $query->query, print_r($values, true)),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $query->execute($values);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        return true;
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
     * @return int|PEAR_Error  number of affected Comics on success, PEAR_Error on failure.
     */
    function removeSum($index = null, $date = null)
    {
        $sql = 'DELETE FROM klutz_comics';

        if (is_null($index) && is_null($date)) {
            $values = array();
        } elseif (is_null($date) && !is_null($index)) {
            $sql .= ' WHERE comicpic_key = ?';
            $values = array($index);
        } elseif (is_null($index) && !is_null($date)) {
            $sql .= ' WHERE comicpic_date = ?';
            $values = array($date);
        } else {
            $sql .= ' WHERE comicpic_key = ? AND comicpic_date = ?';
            $values = array($index, $date);
        }
        $query = $this->_db->prepare($sql);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage('Klutz_Driver_sql::removeSum(): ' . $sql,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $query->execute($values);

        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            return $result->numRows();
            $result->free();
        } else {
            return $result;
        }
    }

    /**
     * Determine if the image passed is a unique image (one we don't already
     * have).
     *
     * This allows for $days = random, etc., but keeps us from getting the same
     * comic day after day.
     *
     * @param Klutz_Image $image  Raw (binary) image data.
     *
     * @return boolean   True if unique, false otherwise.
     */
    function isUnique($image)
    {
        if (!is_a($image, 'Klutz_Image')) {
            return null;
        }

        /* Build the SQL query. */
        $query = $this->_db->prepare('SELECT COUNT(*) FROM klutz_comics WHERE comicpic_hash = ?');
        $params = array(md5($image->data));

        /* Log the query at a DEBUG log level. */
        Horde::logMessage('Klutz_Driver_sql::isUnique(): ' . $query->query,
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $query->execute($params);
        $result = $result->fetchOne();
        if (!is_a($result, 'PEAR_Error') && $result > 0) {
            return false;
        } else {
            return true;
        }
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
     * @return mixed An array of dates in $subdir format between $oldest and
     *               $newest that we have comics for | PEAR_Error
     */
    function listDates($date = null, $oldest = null, $newest = null)
    {

        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        $return = array();

        // Using Date as a reference, return all dates for the same
        // time of day.
        $d = getdate($date);

        if (is_null($oldest)) {
            $oldest = mktime(0, 0, 0, $d['mon'], 1, $d['year']);
        }

        if (is_null($newest)) {
            $newest = mktime(0, 0, 0, $d['mon'] + 1, 0, $d['year']);
        }

        /* Build the SQL query. */
        $query = $this->_db->prepare('SELECT DISTINCT comicpic_date FROM klutz_comics WHERE comicpic_date >= ? AND comicpic_date <= ?');
        $values = array($oldest, $newest);

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('Klutz_Driver_sql::listDates($date = %s, $oldest = %s, $newest = %s): %s',
                          $date, $oldest, $newest, $query->query),
                          __FILE__, __LINE__, PEAR_LOG_DEBUG);

        /* Execute the query. */
        $result = $query->execute($values);


        if (isset($result) && !is_a($result, 'PEAR_Error')) {
            $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            if (is_a($row, 'PEAR_Error')) {
                return $row;
            }

            /* Store the retrieved values in the $return variable. */
            while ($row && !is_a($row, 'PEAR_Error')) {
                /* Add this new date to the $return list. */
                $comicdate = date($this->subdir, $row['comicpic_date']);
                $return[] = mktime(0, 0, 0, substr($comicdate, 4, 2),
                                   substr($comicdate, -2), substr($comicdate, 0, 4));

                /* Advance to the new row in the result set. */
                $row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);
            }
            $result->free();
        } else {
            return $result;
        }

        /* Fallback solution, if the query db doesn't return a result */
        if (count($return) == 0) {
            $d = dir($this->basedir);
            while (false !== ($entry = $d->read())) {
                if (is_dir($d->path . $entry) && strlen($entry) == 8
                    && is_numeric($entry)) {
                    // We're reasonably sure this is a valid dir.
                    $time = mktime(0, 0, 0, substr($entry, 4, 2),
                                   substr($entry, -2), substr($entry, 0, 4));
                    if ($time >= $oldest && $time <= $newest) {
                        $return[] = $time;
                    }
                }
            }
            $d->close();
        }
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
        if (get_class($image) == 'Klutz_Image' && !empty($image->size)) {
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
        if (!is_object($image) || get_class($image) != "Klutz_Image") {
            return false;
        }

        if (is_null($date)) {
            $date = mktime(0, 0, 0);
        }

        // Make sure $this->basedir exists and is writeable.
        if (!file_exists($this->basedir)) {
            if (!file_exists(dirname($this->basedir))) { return false; }
            if (!mkdir($this->basedir, 0700)) { return false; }
        }
        if (!is_writable($this->basedir)) {
            return false;
        }

        $dir = $this->basedir . date($this->subdir, $date);

        if (!file_exists($dir)) {
            mkdir($dir, 0700);
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
