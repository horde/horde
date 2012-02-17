<?php
/**
 * The Horde_SyncMl_Device:: class provides functionality that is potentially
 * (client) device dependant.
 *
 * If a sync client needs any kind of special conversion of the data sent to it
 * or received from it, this is done here. There are two sources of information
 * to identify an device: The first (and better) one is the DevInf device info
 * sent by the device during a request. If DevInf is not supported or sent by
 * the client, the Source/LocURI of the device request might be sufficent to
 * identify it.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Device
{
    /**
     * The original preferred content type of the client, if provided through
     * DevInf.
     *
     * @var string
     */
    public $requestedContentType;

    /**
     * Attempts to return a concrete Horde_SyncMl_Device instance based on $driver.
     *
     * @param string $driver  The type of concrete Horde_SyncMl_Device subclass to
     *                        return.
     *
     * @return Horde_SyncMl_Device  The newly created concrete Horde_SyncMl_Device
     *                        instance, or false on error.
     */
    public function factory($driver)
    {
        $driver = basename($driver);

        if (empty($driver) || $driver == 'none' || $driver == 'default') {
            $GLOBALS['backend']->logMessage(
                'Using default device class', 'DEBUG');
            return new Horde_SyncMl_Device();
        }

        $class = 'Horde_SyncMl_Device_' . $driver;
        if (!class_exists($class)) {
            return false;
        }

        $device = new $class();
        $GLOBALS['backend']->logMessage('Created device class ' . $class, 'DEBUG');
        return $device;
    }

    /**
     * Returns the guessed content type for a database URI.
     *
     * When a client sends data during a sync but does not provide information
     * about the MIME content type with this individual item, this function
     * returns the content type the item is supposed to be in.
     *
     * @param string $database  A database URI.
     *
     * @return string  A MIME type that might match the database URI.
     */
    public function getPreferredContentType($database)
    {
        $database = $GLOBALS['backend']->normalize($database);

        /* Use some wild guessings. */
        if (strpos($database, 'contact') !== false ||
            strpos($database, 'card') !== false) {
            return 'text/x-vcard';
        } elseif (strpos($database, 'note') !== false ||
                  strpos($database, 'memo') !== false) {
            return 'text/plain';
        } elseif (strpos($database, 'task') !== false ||
                  strpos($database, 'cal') !== false ||
                  strpos($database, 'event') !== false) {
            return 'text/calendar';
        }
    }

    /**
     * Returns the preferrred MIME content type of the client for the given
     * sync data type (contacts/tasks/notes/calendar).
     *
     * The result is passed as an option to the backend export functions.
     * This is not the content type ultimately passed to the client but rather
     * the content type presented to the backend export functions.
     *
     * After the data is retrieved from the backend, convertServer2Client()
     * can do some post-processing and set the correct content type acceptable
     * for the client if necessary.
     *
     * The default implementation tries to extract the content type from the
     * device info. If this does not work, some defaults are used.
     *
     * If the client does not provice proper DevInf data, this public function may
     * have to be overwritten to return the correct values.
     *
     * @param string $serverSyncURI  The URI for the server database: contacts,
     *                               notes, calendar or tasks.
     * @param string $sourceSyncURI  The URI for the client database. This is
     *                               needed as the DevInf is grouped by
     *                               sourceSyncURIs.
     */
    public function getPreferredContentTypeClient($serverSyncURI, $sourceSyncURI)
    {
        $di = $GLOBALS['backend']->state->deviceInfo;
        $ds = $di->getDataStore($sourceSyncURI);
        if (!empty($ds)) {
            $r = $ds->getPreferredRXContentType();
            if (!empty($r)) {
                $this->requestedContentType = $r;
                return $r;
            }
        }

        $database = $GLOBALS['backend']->normalize($serverSyncURI);

        /* No information in DevInf, use some wild guessings. */
        if (strpos($database, 'contact') !== false ||
            strpos($database, 'card') !== false) {
            return 'text/x-vcard';
        } elseif (strpos($database, 'note') !== false ||
                  strpos($database, 'memo') !== false) {
            // SyncML conformance suite expects this rather than text/x-vnote
            return 'text/plain';
        } elseif (strpos($database, 'task') !== false ||
                  strpos($database, 'cal') !== false ||
                  strpos($database, 'event') !== false) {
            return 'text/calendar';
        }
    }

    /**
     * Converts the content received from the client for the backend.
     *
     * Currently strips UID (primary key) information as client and server
     * might use different ones.
     *
     * Charset conversions might be added here too.
     *
     * @todo remove UID stripping or move it anywhere else.
     *
     * @param string $content      The content to convert.
     * @param string $contentType  The content type of the content.
     *
     * @return array  Two-element array with the converted content and the
     *                (possibly changed) new content type.
     */
    public function convertClient2Server($content, $contentType)
    {
        $GLOBALS['backend']->logFile(
            Horde_SyncMl_Backend::LOGFILE_DATA,
            "\nInput received from client ($contentType):\n$content\n");

        // Always remove client UID. UID will be seperately passed in XML.
        $content = preg_replace('/(\r\n|\r|\n)UID:.*?(\r\n|\r|\n)/',
                                '\1', $content, 1);

        return array($content, $contentType);
    }

    /**
     * Converts the content from the backend to a format suitable for the
     * client device.
     *
     * Strips the UID (primary key) information as client and server might use
     * different ones.
     *
     * Charset conversions might be added here too.
     *
     * @param string $content      The content to convert
     * @param string $contentType  The content type of content as returned
     *                             from the backend
     * @param string $database     The server database URI.
     *
     * @return array  Three-element array with the converted content, the
     *                (possibly changed) new content type, and encoding type
     *                (like b64 as used by Funambol).
     */
    public function convertServer2Client($content, $contentType, $database)
    {
        if (is_array($contentType)) {
            $contentType = $contentType['ContentType'];
        }

        $GLOBALS['backend']->logFile(
            Horde_SyncMl_Backend::LOGFILE_DATA,
            "\nOutput received from backend ($contentType):\n" . $content
            . "\n");

        /* Always remove server UID. UID will be seperately passed in XML. */
        $content = preg_replace('/(\r\n|\r|\n)UID:.*?(\r\n|\r|\n)/',
                                '\1', $content, 1);

        if ($this->useLocalTime()) {
            $content = preg_replace_callback(
                '/\d{8}T\d{6}Z/',
                array($this, '_convertUTC2LocalTime'),
                $content);
        }

        return array($content, $contentType, null);
    }

    /**
     * Returns whether the device handles tasks and events in a single
     * "calendar" sync.
     *
     * This requires special actions on our side as we store this in different
     * backend databases.
     *
     * @return boolean  True if tasks and events are processed in a single
     *                  request.
     */
    public function handleTasksInCalendar()
    {
        return false;
    }

    /**
     * Returns whether to send individual status response for each Add, Delete
     * and Replace.
     *
     * @return boolean  False if individual status responses should be send.
     */
    public function omitIndividualSyncStatus()
    {
        return false;
    }

    /**
     * Returns whether the payload data should be enclosed in a [CDATA[
     * section when sending via XML.
     *
     * The synchronized data may contain XML special characters like &amp;,
     * &lt; or &gt;. Clients might choke when sending these embedded in XML.
     * The data should be enclosed in [CDATA[ in these cases.  This applies
     * only to XML, not to WBXML devices.
     *
     * @return boolean  True if the data should be enclosed in [CDATA[.
     */
    public function useCdataTag()
    {
        return true;
    }

    /**
     * Returns whether the device accepts datetimes only in local time format
     * (DTSTART:20061222T130000) instead of the more robust UTC time
     * (DTSTART:20061222T110000Z).
     *
     * @return boolean  True if the client doesn't accept UTC datetimes.
     */
    public function useLocalTime()
    {
        return false;
    }

    /**
     * Converts an UTC timestamp like "20061222T110000Z" into a local
     * timestamp like "20061222T130000" using the server timezone.
     *
     * @param array $utc  Array with a datetime string in UTC.
     *
     * @return string  The datetime string converted to the local timezone.
     */
    protected function _convertUTC2LocalTime($utc)
    {
        $date = new Horde_Date($utc[0]);
        $date->setTimezone(date_default_timezone_get());
        return $date->format("Ymd\THis");
    }
}
