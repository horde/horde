<?php
/**
 * Implementation of the Quota API for MDaemon servers.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota_Mdaemon extends IMP_Quota_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'app_location' - (string) [REQUIRED] Location of the application.
     * </pre>
     *
     * @throws IMP_Exception
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['app_location'])) {
            throw new IMP_Exception('Missing app_location parameter in quota config.');
        }

        parent::__construct($params);
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        $imap_ob = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create();
        $userDetails = $this->_getUserDetails(
            $this->_params['username'],
            $_SESSION['imp']['maildomain']
        );

        if ($userDetails !== false) {
            $userHome = trim(substr($userDetails, 105, 90));
            $total = intval(substr($userDetails, 229, 6)) * 1024;

            if ($total == 0) {
                return array('usage' => 0, 'limit' => 0);
            }

            if (($taken = $this->_mailboxSize($userHome)) !== false) {
                return array('usage' => $taken, 'limit' => $total);
            }
        }

        throw new IMP_Exception(_("Unable to retrieve quota"));
    }

    /**
     * Get the size of a mailbox
     *
     * @param string $path  The full path of the mailbox to fetch the quota
     *                     for including trailing backslash.
     *
     * @return mixed  The number of bytes in the mailbox (integer) or false
     *                (boolean) on error.
     */
    protected function _mailboxSize($path)
    {
        $contents = file_get_contents($path . '\imap.mrk');

        $c_len = strlen($contents);
        $pointer = 36;
        $size = 0;

        while ($pointer < $c_len) {
            $details = unpack('a17Filename/a11Crap/VSize', substr($contents, $pointer, 36));
            $size += $details['Size'];
            $pointer += 36;
        }

        /* Recursivly check subfolders. */
        $di = new DirectoryIterator($path);
        foreach ($di as $filename => $entry) {
            if (!$di->isDot() &&
                (substr($filename, -5) == '.IMAP')) {
                $size += $this->_mailboxSize($entry->getPathname() . '\\');
            }
        }

        return $size;
    }

    /**
     * Retrieve relevant line from userlist.dat.
     *
     * @param string $user   The username for which to retrieve detals.
     * @param string $realm  The realm (domain) for the user.
     *
     * @return mixed  Line from userlist.dat (string) or false (boolean).
     */
    protected function _getUserDetails($user, $realm)
    {
        $searchString = str_pad($realm, 45) . str_pad($user, 30);

        if (!($fp = fopen($this->_params['app_location'] . '/userlist.dat', 'rb'))) {
            return false;
        }

        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (substr($line, 0, strlen($searchString)) == $searchString) {
                fclose($fp);
                return $line;
            }
        }
        fclose($fp);

        return false;
    }

}
