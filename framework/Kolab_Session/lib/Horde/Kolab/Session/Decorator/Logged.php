<?php
/**
 * A logger for Horde_Kolab_Session handlers.
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
 * A logger for Horde_Kolab_Session handlers.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Decorator_Logged
extends Horde_Kolab_Session_Decorator_Base
{
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
     * @param Horde_Kolab_Session $session The session handler.
     * @param mixed               $logger  The logger instance.
     */
    public function __construct(
        Horde_Kolab_Session $session,
        $logger
    ) {
        parent::__construct($session);
        $this->_logger  = $logger;
    }

    /**
     * Try to connect the session handler.
     *
     * @param string $user_id     The user ID to connect with.
     * @param array  $credentials An array of login credentials. For Kolab,
     *                            this must contain a "password" entry.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Session_Exception If the connection failed.
     */
    public function connect($user_id = null, array $credentials = null)
    {
        try {
            $this->_session->connect($user_id, $credentials);
            $this->_logger->info(
                sprintf(
                    "Connected Kolab session for \"%s\".",
                    $this->_session->getId()
                )
            );
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->_logger->err(
                sprintf(
                    "Failed to connect Kolab session for \"%s\". Error was: %s",
                    $this->_session->getMail(), $e->getMessage()
                )
            );
            throw $e;
        }
    }

    /**
     * Export the session data as array.
     *
     * @return array The session data.
     */
    public function export()
    {
        $session_data = $this->_session->export();
        $this->_logger->info(
            sprintf(
                "Exported session data for \"%s\" (%s).",
                $this->_session->getMail(), serialize($session_data)
            )
        );
        return $session_data;
    }

    /**
     * Import the session data from an array.
     *
     * @param array The session data.
     *
     * @return NULL
     */
    public function import(array $session_data)
    {
        $this->_session->import($session_data);
        $this->_logger->info(
            sprintf(
                "Imported session data for \"%s\" (%s).",
                $this->_session->getMail(), serialize($session_data)
            )
        );
    }

    /**
     * Clear the session data.
     *
     * @return NULL
     */
    public function purge()
    {
        $this->_logger->warn(
            sprintf(
                "Purging session data for \"%s\".",
                $this->_session->getMail()
            )
        );
        $this->_session->purge();
    }
}
