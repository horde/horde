<?php
/**
 * Implementation of the Quota API for servers using Maildir++ quota files on
 * the local filesystem.  Currently only supports storage limit, but should be
 * expanded to be configurable to support storage or message limits in the
 * configuration array.
 *
 * Requires the following parameter settings in imp/servers.php:
 * <pre>
 * 'quota' => array(
 *     'driver' => 'maildir',
 *     'params' => array(
 *         'path' => '/path/to/users/Maildir'
 *         // TODO: Add config param for storage vs message quota
 *     )
 * );
 *
 * path - The path to the user's Maildir directory. You may use the
 *        two-character sequence "~U" to represent the user's account name,
 *        and the actual username will be substituted in that location.
 *        E.g., '/home/~U/Maildir/' or '/var/mail/~U/Maildir/'
 * </pre>
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @package IMP
 */
class IMP_Quota_Maildir extends IMP_Quota
{
    /**
     * Constructor.
     *
     * @param array $params  Hash containing connection parameters.
     */
    protected function __construct($params = array())
    {
        parent::__construct(array_merge(array('path' => ''), $params));
    }

    /**
     * Returns quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws Horde_Exception
     */
    public function getQuota()
    {
        $storage_limit = $message_limit = $storage_used = $message_used = 0;

        // Get the full path to the quota file.
        $full = $this->_params['path'] . '/maildirsize';

        // Substitute the username in the string if needed.
        $uname = $_SESSION['imp']['user'];
        $full = str_replace('~U', $uname, $full);

        // Read in the quota file and parse it, if possible.
        if (!is_file($full)) {
            throw new Horde_Exception(_("Unable to retrieve quota"));
        }

        // Read in maildir quota file.
        $lines = file($full);

        // Parse the lines.
        foreach ($lines as $line_number => $line) {
            if ($line_number == 0) {
                // First line, quota header.
                $line = preg_replace('/[ \t\n\r\0\x0B]/', '', $line);
                list($v1, $t1, $v2, $t2) = sscanf($line, '%ld%[CS],%ld%[CS]');
                if (is_null($v1) || is_null($t1)) {
                    $v1 = 0;
                }
                if (is_null($v2) || is_null($t2)) {
                    $v2 = 0;
                }

                if ($t1 == 'S') {
                    $storage_limit = $v1;
                }
                if ($t1 == 'C') {
                    $message_limit = $v1;
                }
                if ($t2 == 'S') {
                    $storage_limit = $v2;
                }
                if ($t2 == 'C') {
                    $message_limit = $v2;
                }
            } else {
                // Any line other than the first line.
                // The quota used is the sum of all lines found.
                list($storage, $message) = sscanf(trim($line), '%ld %d');
                if (!is_null($storage)) {
                    $storage_used += $storage;
                }
                if (!is_null($message)) {
                    $message_used += $message;
                }
            }
        }

        return array('usage' => $storage_used, 'limit' => $storage_limit);
    }

}
