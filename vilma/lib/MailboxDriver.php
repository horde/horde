<?php
/**
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Vilma
 */
abstract class Vilma_MailboxDriver
{
    /**
     * A hash containing any parameters for the current driver.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Any parameters needed for this driver.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    public function getParam($param)
    {
        return isset($this->_params[$param]) ? $this->_params[$param] : null;
    }

    /**
     * Creates a new mailbox.
     *
     * @param string $user    The name of the mailbox to create.
     * @param string $domain  The name of the domain in which to create the
     *                        mailbox.
     *
     * @throws Vilma_Exception
     */
    abstract public function createMailbox($user, $domain);

    /**
     * Deletes an existing mailbox.
     *
     * @param string $user    The name of the mailbox to delete.
     * @param string $domain  The name of the domain in which to delete the
     *                        mailbox.
     *
     * @throws Vilma_Exception
     */
    abstract public function deleteMailbox($user, $domain);

    /**
     * Checks whether a mailbox exists and is set up properly.
     *
     * @param string $user    The name of the mailbox to check.
     * @param string $domain  The mailbox' domain.
     *
     * @return boolean  True if the mailbox exists.
     * @throws Vilma_Exception if the mailbox doesn't exist.
     */
    abstract public function checkMailbox($user, $domain);

    /**
     * Creates a new mailbox driver instance.
     *
     * @param string $driver  The name of the driver to create an instance of.
     * @param array $params   Driver-specific parameters.
     *
     * @return Vilma_MailboxDriver  The new driver instance.
     * @throws Vilma_Exception
     */
    static public function factory($driver = null, $params = null)
    {
        if (is_null($driver)) {
            $driver = $GLOBALS['conf']['mailboxes']['driver'];
        }
        $driver = Horde_String::ucfirst(basename($driver));

        if (is_null($params)) {
            $params = $GLOBALS['conf']['mailboxes']['params'];
        }

        $class = 'Vilma_MailboxDriver_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        }

        throw new Vilma_Exception(sprintf(_("No such mailbox driver \"%s\" found"), $driver));
    }
}
