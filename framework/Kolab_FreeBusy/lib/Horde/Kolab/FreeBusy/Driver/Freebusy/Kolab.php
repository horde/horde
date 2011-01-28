<?php
/**
 * The Kolab implementation of the free/busy system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The Horde_Kolab_FreeBusy class serves as Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Driver_Freebusy_Kolab extends Horde_Kolab_FreeBusy_Driver_Freebusy_Base
{
    /**
     * Fetch the free/busy data for a user.
     *
     * @params array   $params   Additional options.
     * <pre>
     * 'extended' - Whether to fetch extended free/busy information or not.
     * </pre>
     *
     * @return array The free/busy data.
     */
    public function fetch($params = array())
    {
        $extended = !empty($params['extended']);

    }

    /**
     * Parse the owner value.
     *
     * @param string $owner The owner that should be processed.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    protected function handleCallee($callee)
    {
        $this->owner = $owner;

        $result = $this->_process();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        return true;
    }

    /**
     * Parse the requested folder for the owner of that folder.
     *
     * @param string $req_folder The folder requested.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    protected function handleCalleePart($callee_part)
    {
        /* Handle the owner/folder name and make sure the owner part is in lower case */
        $req_folder = Horde_String::convertCharset($req_folder, 'UTF-8', 'UTF7-IMAP');
        $folder = explode('/', $req_folder);
        if (count($folder) < 2) {
            return PEAR::raiseError(sprintf(Horde_Kolab_FreeBusy_Translation::t("No such folder %s"), $req_folder));
        }

        $folder[0] = strtolower($folder[0]);
        $req_folder = implode('/', $folder);
        $this->owner = $folder[0];
        unset($folder[0]);
        $this->folder = join('/', $folder);

        $result = $this->_process();
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }
        return true;
    }

    /**
     * Process both the user accessing the page as well as the
     * owner of the requested free/busy information.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    private function _process()
    {
        global $conf;

        require_once 'Horde/Kolab/Server.php';

        if (isset($conf['kolab']['ldap']['phpdn'])) {
            $params = array(
                'uid' => $conf['kolab']['ldap']['phpdn'],
                'pass' => $conf['kolab']['ldap']['phppw'],
            );
        } else {
            $params = array(
                'user' => $GLOBALS['registry']->getAuth(),
                'pass' => $GLOBALS['registry']->getAuthCredential('password')
            );
        }

        /* Connect to the Kolab user database */
        $db = &Horde_Kolab_Server::singleton($params);
        // TODO: Remove once Kolab_Server has been fixed to always return the base dn
        $db->fetch();

        /* Retrieve the server configuration */
        try {
            $server = $db->fetch(sprintf('k=kolab,%s',
                                         $db->getBaseUid()),
                                 'Horde_Kolab_Server_Object_Kolab_Server');
            $this->server_object = $server;
        } catch (Horde_Kolab_Server_Exception $e) {
            Horde::logMessage(sprintf("Failed fetching the k=kolab configuration object. Error was: %s",
                                      $e->getMessage()),
                              __FILE__, __LINE__, PEAR_LOG_ERR);
            $this->server_object = null;
        }

        /* Fetch the user calling us */
        $udn = $db->uidForIdOrMail($this->user);
        if (is_a($udn, 'PEAR_Error')) {
            return $udn;
        }
        if ($udn) {
            $user = $db->fetch($udn, 'Horde_Kolab_Server_Object_Kolab_User');
            if (is_a($user, 'PEAR_Error')) {
                return $user;
            }
            $this->user_object = $user;
        }

        if ($this->user_object && $this->user_object->exists()) {
            $mail = $this->user_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL);
            if (is_a($mail, 'PEAR_Error')) {
                return $mail;
            }
            if ($mail) {
                $this->user = $mail;
            }
        }

        /* Fetch the owner of the free/busy data */
        $odn = $db->uidForIdOrMailOrAlias($this->owner);
        if (is_a($odn, 'PEAR_Error')) {
            return $odn;
        }
        if (!$odn) {
            $idx = strpos($this->user, '@');
            if($idx !== false) {
                $domain = substr($this->user, $idx+1);
                Horde::logMessage(sprintf("Trying to append %s to %s",
                                          $domain, $this->owner),
                                  __FILE__, __LINE__, PEAR_LOG_DEBUG);
                $odn = $odn = $db->uidForIdOrMail($this->owner . '@' . $domain);
            }
        }

        if ($odn) {
            $owner = $db->fetch($odn, 'Horde_Kolab_Server_Object_Kolab_User');
            if (is_a($owner, 'PEAR_Error')) {
                return $owner;
            }
            $this->owner_object = &$owner;
        }

        if (!empty($this->owner_object)) {
            if ($this->owner_object->exists()) {
                $this->owner = $this->owner_object->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_MAIL);

                $freebusyserver = $this->owner_object->getServer('freebusy');
                if (!is_a($freebusyserver, 'PEAR_Error')) {
                    $this->freebusyserver = $freebusyserver;
                }
            }
        } else {
            return PEAR::raiseError(Horde_Kolab_FreeBusy_Translation::t("Unable to determine owner of the free/busy data!"));
        }

        /* Mangle the folder request into an IMAP folder name */
        $this->imap_folder = $this->_getImapFolder();

        return true;
    }

    /**
     * Calculate the correct IMAP folder name to access based on the
     * combination of user and owner.
     *
     * @return string The IMAP folder we should access.
     */
    function _getImapFolder()
    {
        $userdom = false;
        $ownerdom = false;
        if (ereg( '(.*)@(.*)', $this->user, $regs)) {
            // Regular user
            $user = $regs[1];
            $userdom  = $regs[2];
        } else {
            $user = $this->user;
        }

        if(ereg( '(.*)@(.*)', $this->owner, $regs)) {
            // Regular owner
            $owner = $regs[1];
            $ownerdom = $regs[2];
        } else {
            $owner = $this->owner;
        }

        $fldrcomp = array();
        if ($user == $owner) {
            $fldrcomp[] = 'INBOX';
        } else {
            $fldrcomp[] = 'user';
            $fldrcomp[] = $owner;
        }

        if (!empty($this->folder)) {
            $fldrcomp[] = $this->folder;
        }

        $folder = join('/', $fldrcomp);
        if ($ownerdom && !$userdom) {
            $folder .= '@' . $ownerdom;
        }
        return $folder;
    }
}
