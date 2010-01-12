<?php
/**
 * Horde_SessionHandler:: implementation for PHP's built-in session handler.
 *
 * Required parameters:<pre>
 *   None.</pre>
 *
 * Optional parameters:<pre>
 *   None.</pre>
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Matt Selsky <selsky@columbia.edu>
 * @package Horde_SessionHandler
 */
class Horde_SessionHandler_None extends Horde_SessionHandler
{
    /**
     * Read the data for a particular session identifier from the backend.
     *
     * @param string $id  The session identifier.
     *
     * @return string  The session data.
     */
    protected function _read($id)
    {
        $file = session_save_path() . DIRECTORY_SEPARATOR . 'sess_' . $id;
        $session_data = @file_get_contents($file);
        if ($session_data === false) {
            Horde::logMessage('Unable to read file: ' . $file, __FILE__, __LINE__, PEAR_LOG_ERR);
            $session_data = '';
        }

        return $session_data;
    }

    /**
     * Get a list of the valid session identifiers.
     *
     * @return array  A list of valid session identifiers.
     */
    public function getSessionIDs()
    {
        $sessions = array();

        $path = session_save_path();
        $d = @dir(empty($path) ? Horde_Util::getTempDir() : $path);
        if (!$d) {
            return $sessions;
        }

        while (($entry = $d->read()) !== false) {
            /* Make sure we're dealing with files that start with
             * sess_. */
            if (is_file($d->path . DIRECTORY_SEPARATOR . $entry) &&
                !strncmp($entry, 'sess_', strlen('sess_'))) {
                $sessions[] = substr($entry, strlen('sess_'));
            }
        }

        return $sessions;
    }

}
