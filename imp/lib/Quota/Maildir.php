<?php
/**
 * Implementation of the Quota API for servers using Maildir++ quota files on
 * the local filesystem.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota_Maildir extends IMP_Quota_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'msg_count' - (boolean) Display information on the message limit rather
     *               than the storage limit?
     *               DEFAULT: false
     * 'path' - (string) The path to the user's Maildir directory. You may use
     *          the two-character sequence "~U" to represent the user's
     *          account name, and the actual username will be substituted in
     *          that location.
     *          E.g., '/home/~U/Maildir/' or '/var/mail/~U/Maildir/'
     *          DEFAULT: ''
     * 'username' - (string) Username to substitute into the string.
     *              DEFAULT: none
     * </pre>
     */
    public function __construct($params = array())
    {
        parent::__construct(array_merge(array(
            'msg_count' => false,
            'path' => '',
            'username' => ''
        ), $params));
    }

    /**
     * Returns quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'limit' = Maximum quota allowed.
     * 'usage' = Currently used portion of quota (in bytes).
     * </pre>
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        $limit = $used = 0;

        // Get the full path to the quota file.
        $full = $this->_params['path'] . '/maildirsize';

        // Substitute the username in the string if needed.
        $full = str_replace('~U', $this->_params['username'], $full);

        // Read in the quota file and parse it, if possible.
        if (!is_file($full)) {
            throw new IMP_Exception(_("Unable to retrieve quota"));
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

                if ($this->_params['msg_count']) {
                    if ($t1 == 'C') {
                        $limit = $v1;
                    }
                    if ($t2 == 'C') {
                        $limit = $v2;
                    }
                } else {
                    if ($t1 == 'S') {
                        $limit = $v1;
                    }
                    if ($t2 == 'S') {
                        $limit = $v2;
                    }
                }
            } else {
                // Any line other than the first line.
                // The quota used is the sum of all lines found.
                list($storage, $message) = sscanf(trim($line), '%ld %d');
                if ($this->_params['msg_count'] && !is_null($message)) {
                    $used += $message;
                } elseif (!$this->_params['msg_count'] && !is_null($storage)) {
                    $used += $storage;
                }
            }
        }

        return array(
            'limit' => $limit,
            'usage' => $used
        );
    }

}
