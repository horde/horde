<?php
/**
 * Implements the Accounts API for servers with unix accounts on the localhost
 * machine (same machine as the web server).  Should work for local unix
 * accounts, nis/nis+ accounts, or any PAM oriented accounts that appear as
 * local accounts on the local machine.  The exception is the quota support.
 * See that routine for additional comments.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde
 */
class Horde_Block_Account_Localhost extends Horde_Block_Account_Base
{
    /**
     * User information hash.
     *
     * @var array
     */
    protected $_information;

    /**
     * Constructor.
     *
     * @param array $params  Hash containing connection parameters.
     */
    public function __construct($params = array())
    {
        $params = array_merge(
            array('quota_path' => 'quota',
                  'grep_path'  => 'grep'),
            $params);
        parent::__construct($params);
    }

    /**
     * Returns the user account from the posix information.
     *
     * @return array  A hash with complete account details.
     *
     * @throws Horde_Exception if posix extension is missing.
     */
    protected function _getAccount()
    {
        if (!isset($this->_information)) {
            // This won't work if we don't have posix extensions.
            if (!Horde_Util::extensionExists('posix')) {
                throw new Horde_Exception(_("POSIX extension is missing"));
            }
            $user = Horde_String::lower($this->getUsername());
            $this->_information = posix_getpwnam($user);
        }
        return $this->_information;
    }

    /**
     * Returns the user's quota for servers with a unix quota command.
     *
     * This may require a modified "quota" command that allows the httpd
     * server account to get quotas for other users...  It requires that your
     * web server and user server be the same server or at least have shared
     * authentication and file servers (e.g. via NIS/NFS).  And last, it (as
     * written) requires the posix php extensions.
     *
     * If your quota command wraps the output onto two lines, then this module
     * will only work if you have a grep which supports the -A switch, and you
     * append an -A1 switch to your grep_path (e.g. '/bin/grep -A1').
     *
     * @return array  A quota array, elements are used bytes and limit bytes.
     *
     * @throws Horde_Exception if posix extension is missing.
     */
    public function getQuota()
    {
        $information = $this->_getAccount();
        $homedir = $information['dir'];

        // If we want mount point translations, then translate the login dir
        // name to a mount point.  If not, then simply parse out the device
        // name from the login directory, and use that instead.
        if ($this->_params['translateMountPoint'] &&
            file_exists($this->_params['translationTable'])) {
            $sysTab = File_Fstab::singleton($this->_params['translationTable']);
            do {
                $entry = $sysTab->getEntryForPath($homedir);
                $homedir = dirname($homedir);
                if ($homedir == '.' || empty($homedir)) {
                    $homedir = '/';
                }
            } while (is_a($entry, 'PEAR_Error'));
            $mountPoint = $entry->device;
        } else {
            $homedir = explode('/', $homedir);
            $mountPoint = '/' . $homedir[1];
        }

        $cmdline = sprintf('%s -u %s 2>&1 | %s %s',
                           $this->_params['quota_path'],
                           $this->getUserName(),
                           $this->_params['grep_path'],
                           $mountPoint);
        $junk = exec($cmdline, $quota_data, $return_code);
        if ($return_code == 0 && !empty($quota_data[0])) {
            // In case of quota output wrapping on two lines, we concat the
            // second line of results, if any, here.
            if (!empty($quota_data[1])) {
                $quota_data[0] .= $quota_data[1];
            }
            // Now parse out the quota info and return it.
            $quota = preg_split('/\s+/', trim($quota_data[0]));
            return array('used' => $quota[1] * 1024, 'limit' => $quota[2] * 1024);
        }

        return array();
    }

    /**
     * Returns the user's full name.
     *
     * @return string  The user's full name.
     *
     * @throws Horde_Exception if posix extension is missing.
     */
    public function getFullname()
    {
        $information = $this->_getAccount();
        $gecos_array = explode(',', $information['gecos']);
        return $gecos_array[0];
    }

    /**
     * Returns the user's home (login) directory.
     *
     * @return string  The user's directory.
     *
     * @throws Horde_Exception if posix extension is missing.
     */
    public function getHome()
    {
        $information = $this->_getAccount();
        return $information['dir'];
    }

    /**
     * Returns the user's default shell.
     *
     * @return string  The user's shell.
     *
     * @throws Horde_Exception if posix extension is missing.
     */
    public function getShell()
    {
        $information = $this->_getAccount();
        return $information['shell'];
    }
}
