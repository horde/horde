<?php
/**
 * The Horde_SyncMl_Device_Sync4JMozilla:: class provides functionality that is
 * specific to the Sync4JMozilla Plugin. See
 * http://sourceforge.net/projects/sync4jmozilla/
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_Device_Sync4JMozilla extends Horde_SyncMl_Device
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


        /* The plugin does currently not handle lines that are both folded
         * and QUOTED-PRINTABLE encoded. Like this one with a note "abc":
         * NOTE;ENCODING=QUOTED-PRINTABLE;CHARSET=UTF-8:=
         * a=
         * bc
         */

        if (preg_match_all('/\r\n[^:]*ENCODING=QUOTED-PRINTABLE[^:]*:.*?=\r\n.*?[^=](?=\r\n)/mis', $content, $m)) {
            foreach($m[0] as $v) {
                /* Remove line folding */
                $content = str_replace($v,str_replace("=\r\n", '', $v), $content);
            }
        }
        $l = "\noutput converted for client ($contentType):\n" . $content . "\n";
        $GLOBALS['backend']->logFile(Horde_SyncMl_Backend::LOGFILE_DATA, $l);

        return array($content, $contentType, $encodingType);
    }
}
