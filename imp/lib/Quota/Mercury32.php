<?php
/**
 * Implementation of the Quota API for Mercury/32 IMAP servers.
 * For reading Quota, read size folder user.
 *
 *****************************************************************************
 * PROBLEM TO ACCESS NETWORK DIRECTORY
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
 * @author   Frank Lupo <frank_lupo@email.it>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota_Mercury32 extends IMP_Quota_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'mail_user_folder' - (string) [REQUIRED] The path to folder mail
                             mercury.
     * </pre>
     *
     * @throws IMP_Exception
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['mail_user_folder'])) {
            throw new IMP_Exception('Missing mail_user_folder parameter in quota config.');
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
        $quota = 0;

        try {
            $di = new DirectoryIterator($this->_params['mail_user_folder'] . '/' . $this->_params['username'] . '/');
        } catch (UnexpectedValueException $e) {
            throw new IMP_Exception(_("Unable to retrieve quota"));
        }

        foreach ($di as $val) {
            $quota += $val->getSize();
        }

        return array(
            'limit' => 0,
            'usage' => $quota
        );
    }

}
