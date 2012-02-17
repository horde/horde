<?php
/**
 * The Horde_SyncMl_Device_Nokia:: class provides functionality that is
 * specific to the Nokia SyncML clients.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Device_Nokia extends Horde_SyncMl_Device
{
    /**
     * Converts the content received from the client for the backend.
     *
     * @param string $content      The content to convert.
     * @param string $contentType  The content type of the content.
     *
     * @return array  Two-element array with the converted content and the
     *                (possibly changed) new content type.
     */
    public function convertClient2Server($content, $contentType)
    {
        list($content, $contentType) =
            parent::convertClient2Server($content, $contentType);

        /* At least the Nokia E series seems to prefix category values with
         * X-, see bugs #6849 and #7824. */
        $di = $GLOBALS['backend']->state->deviceInfo;
        if ($di->Mod[0] == 'E') {
            $content = preg_replace('/(\r\n|\r|\n)CATEGORIES:X-/',
                                    '\1CATEGORIES:', $content, 1);
        }

        $GLOBALS['backend']->logFile(
            Horde_SyncMl_Backend::LOGFILE_DATA,
            "\nInput converted for server ($contentType):\n$content\n");

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
        $database = $GLOBALS['backend']->normalize($database);

        list($content, $contentType, $encodingType) =
            parent::convertServer2Client($content, $contentType, $database);

        $content = preg_replace('/(\r\n|\r|\n)PHOTO;ENCODING=b[^:]*:(.+?)(\r\n|\r|\n)/',
                                '\1PHOTO;ENCODING=BASE64:\1\2\1\1',
                                $content, 1);

        $GLOBALS['backend']->logFile(
            Horde_SyncMl_Backend::LOGFILE_DATA,
            "\nOutput converted for client ($contentType):\n$content\n");

        return array($content, $contentType, null);
    }

    public function handleTasksInCalendar()
    {
        return true;
    }

    /**
     * Some devices accept datetimes only in local time format:
     * DTSTART:20061222T130000
     * instead of the more robust (and default) UTC time:
     * DTSTART:20061222T110000Z
     */
    public function useLocalTime()
    {
        return true;
    }
}
