<?php
/**
 * Logs access to the export system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Logs access to the export system.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 * @since    Horde 3.2
 */
class Horde_Kolab_FreeBusy_User_Decorator_Log
implements Horde_Kolab_FreeBusy_User
{
    /**
     * The decorated user.
     *
     * @var Horde_Kolab_FreeBusy_User
     */
    private $_user;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * The remote .
     *
     * @var mixed
     */
    private $_remote;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_User $user    The decorated user.
     * @param Horde_Controller_Request  $request The request.
     * @param mixed                     $logger  The log handler. The class must
     *                                           at least provide the notice()
     *                                           and err() methods.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_User $user,
        Horde_Controller_Request $request,
        $logger
    )
    {
        $this->_user   = $user;
        $this->_logger = $logger;
        $vars = $request->getServerVars();
        $this->_remote = isset($vars['REMOTE_ADDR']) ? $vars['REMOTE_ADDR'] : 'unknown';
    }

    /**
     * Return the primary id of the user accessing the system.
     *
     * @return string The primary id.
     */
    public function getPrimaryId()
    {
        return $this->_user->getPrimaryId();
    }

    /**
     * Return the password of the user accessing the system.
     *
     * @return string The password.
     */
    public function getPassword()
    {
        return $this->_user->getPassword();
    }

    /**
     * Return the primary domain of the user accessing the system.
     *
     * @return string The primary domain.
     */
    public function getDomain()
    {
        return $this->_user->getDomain();
    }

    /**
     * Return the groups this user is member of.
     *
     * @return array The groups for this user.
     */
    public function getGroups()
    {
        return $this->_user->getGroups();
    }


    /**
     * Finds out if a set of login credentials are valid.
     *
     * @return boolean Whether or not the password was correct.
     */
    public function isAuthenticated()
    {
        $result = $this->_user->isAuthenticated();
        $id = $this->_user->getPrimaryId();
        if ($result) {
            $this->_logger->notice(
                sprintf(
                    'Login success for "%s" from "%s" to free/busy.',
                    $id,
                    $this->_remote
                )
            );
        } else {
            if (!empty($id)) {
                $this->_logger->err(
                    sprintf(
                        'Failed login for "%s" from "%s" to free/busy',
                        $id,
                        $this->_remote
                    )
                );
            } else {
                $this->_logger->notice(
                    sprintf(
                        'Anonymous access from "%s" to free/busy.',
                        $this->_remote
                    )
                );
            }
        }
        return $result;
    }
}