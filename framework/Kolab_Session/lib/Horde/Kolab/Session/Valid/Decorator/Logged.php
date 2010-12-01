<?php
/**
 * A logger for Horde_Kolab_Session_Valid validators.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * A logger for Horde_Kolab_Session_Valid validators.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Valid_Decorator_Logged
implements Horde_Kolab_Session_Valid
{
    /**
     * The valid handler.
     *
     * @var Horde_Kolab_Session_Valid_Interface
     */
    private $_valid;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * The provided logger class needs to implement the methods info() and
     * err().
     *
     * @param Horde_Kolab_Session_Valid_Interface $valid  The validator.
     * @param mixed                               $logger The logger instance.
     */
    public function __construct(
        Horde_Kolab_Session_Valid $valid,
        $logger
    ) {
        $this->_valid  = $valid;
        $this->_logger = $logger;
    }

    /**
     * Reset the current session information in case it does not match the
     * authentication information anymore.
     *
     * @param string $user The user the session information is being requested
     *                     for. This is usually empty, indicating the current
     *                     user.
     *
     * @return boolean True if the session is still valid.
     */
    public function validate($user = null)
    {
        $this->_logger->info(
            sprintf(
                "Validating Kolab session for current user \"%s\", requested"
                . " user \"%s\", and stored user \"%s\".",
                $this->_valid->getAuth(),
                $user,
                $this->_valid->getSession()->getMail()
            )
        );
        $result = $this->_valid->validate($user);
        if ($result === false) {
            $this->_logger->info(
                sprintf(
                    "Invalid Kolab session for current user \"%s\" and requested"
                    . " user \"%s\".",
                    $this->_valid->getAuth(),
                    $user
                )
            );
        }
        return $result;
    }

    /**
     * Return the session this validator checks.
     *
     * @return Horde_Kolab_Session The session checked by this
     * validator.
     */
    public function getSession()
    {
        return $this->_valid->getSession();
    }

    /**
     * Return the auth driver of this validator.
     *
     * @return mixed The user ID or false if no user is logged in.
     */
    public function getAuth()
    {
        return $this->_valid->getAuth();
    }
}
