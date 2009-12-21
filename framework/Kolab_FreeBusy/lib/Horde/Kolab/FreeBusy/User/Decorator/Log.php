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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Logs access to the export system.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
 * @since    Horde 3.2
 */
class Horde_Kolab_FreeBusy_User_Decorator_Log
{
    /**
     * The decorated user.
     *
     * @var Horde_Kolab_FreeBusy_User_Interface
     */
    private $_user;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_User_Interface $user   The decorated
     *                                                    user.
     * @param mixed                               $logger The log handler. The
     *                                                    class must at least
     *                                                    provide the notice()
     *                                                    and err() methods.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_User_Interface $user,
        $logger
    ) {
        $this->_user   = $user;
        $this->_logger = $logger;
    }

    /**
     * Finds out if a set of login credentials are valid.
     *
     * @param array $pass The password to check.
     *
     * @return boolean  Whether or not the password was correct.
     */
    public function authenticate($pass)
    {
        $result = $this->_user->authenticate($pass);
            if ($result) {
                $this->_logger->notice(
                    sprintf(
                        'Login success for %s from %s to free/busy.', $this->user, $_SERVER['REMOTE_ADDR']));
            } else {
                $this->_logger->err(sprintf('Failed login for %s from %s to free/busy', $this->user, $_SERVER['REMOTE_ADDR']));
            }
    }
}