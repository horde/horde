<?php
/**
 * Implementation of the Quota API for Mercury/32 IMAP servers.
 * For reading Quota, read size folder user.
 *
 * You must configure this driver in imp/config/servers.php.  The driver
 * supports the following parameters:
 * <pre>
 * 'mail_user_folder' - (string) The path to folder mail mercury.
 * </pre>
 *
 *****************************************************************************
 * PROBLEM TO ACCESS NETWORK DIRECOTRY
 *****************************************************************************
 * Matt Grimm
 * 06-Jun-2003 10:25
 *
 * Thought I could help clarify something with accessing network shares on a
 * Windows network (2000 in this case), running PHP 4.3.2 under Apache 2.0.44.
 * However you are logged into the Windows box, your Apache service must be
 * running under an account which has access to the share.  The easiest (and
 * probably least safe) way for me was to change the user for the Apache
 * service to the computer administrator (do this in the service properties,
 * under the "Log On" tab).  After restarting Apache, I could access mapped
 * drives by their assigned drive letter ("z:\\") or regular shares by their
 * UNC path ("\\\\shareDrive\\shareDir").
 *****************************************************************************
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Frank Lupo <frank_lupo@email.it>
 * @package IMP
 */
class IMP_Quota_Mercury32 extends IMP_Quota
{
    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     */
    public function getQuota()
    {
        $quota = null;

        $dir_path = $this->_params['mail_user_folder'] . '/' . $_SESSION['imp']['user'] . '/';
        if ($dir = @opendir($dir_path)) {
            while (($file = readdir($dir)) !== false) {
                $quota += filesize($dir_path . $file);
            }
            closedir($dir);

            if (!is_null($quota)) {
                return array('usage' => $quota, 'limit' => 0);
            }
        }

        throw new Horde_Exception(_("Unable to retrieve quota"));
    }

}
