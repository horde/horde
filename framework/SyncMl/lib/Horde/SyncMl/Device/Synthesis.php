<?php
/**
 * The Horde_SyncMl_Device_Synthesis:: class provides functionality that is
 * specific to the Synthesis.ch SyncML clients.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Device_Synthesis extends Horde_SyncMl_Device
{
    /**
     * Converts the content from the backend to a format suitable for the
     * client device.
     *
     * Strips the uid (primary key) information as client and server might use
     * different ones.
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
        list($content, $contentType, $encodingType) =
            parent::convertServer2Client($content, $contentType, $database);

        $di = $GLOBALS['backend']->state->deviceInfo;
        if (stristr($di->Mod,'palm') === false) {
            // Some special priority handling is required. Synthesis uses
            // 1 (high), 2 (medium), 3(low), at least for my windows mobile device.
            // convert these to valid priority settings:
            $content = preg_replace('/(\r\n|\r|\n)PRIORITY:[1-2](\r\n|\r|\n)/', '\1PRIORITY:1\2', $content, 1);
            $content = preg_replace('/(\r\n|\r|\n)PRIORITY:[3](\r\n|\r|\n)/', '\1PRIORITY:2\2', $content, 1);
            $content = preg_replace('/(\r\n|\r|\n)PRIORITY:[4-9](\r\n|\r|\n)/', '\1PRIORITY:3\2', $content, 1);
        }
        // Windows Mobile also expects DUE DATES like DUE:20060419T000000
        if (preg_match('/(\r\n|\r|\n)DUE:(........T......Z)(\r\n|\r|\n)/',
                $content,$m)) {
            $m[2] = $this->UTC2LocalDate($m[2]);
            $content = preg_replace('/(\r\n|\r|\n)DUE:(........T......Z)(\r\n|\r|\n)/',
                '\1DUE:' . $m[2] . '\3', $content, 1);
        }

        $l = "\noutput converted for client ($contentType):\n" . $content . "\n";
        $GLOBALS['backend']->logFile(Horde_SyncMl_Backend::LOGFILE_DATA, $l);

        return array($content, $contentType, $encodingType);
    }

    /**
     * Convert the content.
     *
     * @param string $content       The content to convert.
     * @param string $contentType   The contentType of the content.
     * @return array                array($newcontent, $newcontentType):
     *                              the converted content and the
     *                              (possibly changed) new ContentType.
     */
    public function convertClient2Server($content, $contentType)
    {
        list($content, $contentType) =
            parent::convertClient2Server($content, $contentType);

        $di = $GLOBALS['backend']->state->deviceInfo;
        if (stristr($di->Mod, 'palm') === false) {
            // Some special priority handling is required. Synthesis uses 1
            // (high), 2 (medium), 3(low), at least for my windows mobile
            // device.  convert these to valid priority settings:
            $content = preg_replace('/(\r\n|\r|\n)PRIORITY:3(\r\n|\r|\n)/',
                                    '\1PRIORITY:5\2', $content, 1);
            $content = preg_replace('/(\r\n|\r|\n)PRIORITY:2(\r\n|\r|\n)/',
                                    '\1PRIORITY:3\2', $content, 1);
        }

        $GLOBALS['backend']->logFile(
            Horde_SyncMl_Backend::LOGFILE_DATA,
            "\ninput converted for server ($contentType):\n$content\n");

        return array($content, $contentType);

    }


    /* Static helper function: converts a UTC Timestamp like 20060418T220000Z
     * into a local date like 20060419T000000. This is actually more than
     * stripping the time part: we need to convert to local time first to ensure
     * we get the right date!
     */
    public function UTC2LocalDate($s)
    {
        $date = new Horde_Date($s);
        $date->setTimezone(date_default_timezone_get());
        return $date->format('Ymd') . 'T000000';
    }
}
