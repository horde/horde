<?php
/**
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Vilma
 */
class Vilma_MailboxDriver {

    var $_params;

    /**
     * Constructor.
     *
     * @access private
     */
    function Vilma_MailboxDriver($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Creates a new mailbox driver instance.
     *
     * @param string $driver  The name of the driver to create an instance of.
     * @param array $params   Driver-specific parameters.
     *
     * @return Vilma_MailboxDriver  The new driver instance or a PEAR_Error.
     */
    function &factory($driver, $params = array())
    {
        require_once VILMA_BASE . '/lib/MailboxDriver/' . $driver . '.php';
        $class = 'Vilma_MailboxDriver_' . $driver;
        $mailbox = &new $class($params);
        return $mailbox;
    }

    /**
     * Returns a mailbox driver instance with the specified params, creating
     * it if necessary.
     *
     * @param string $driver  The name of the driver to create an instance of.
     * @param array $params   Driver-specific parameters.
     *
     * @return Vilma_MailboxDriver  The new driver instance or a PEAR_Error.
     */
    function &singleton($driver, $params = array())
    {
        static $cache;
        $key = serialize(array($driver, $params));
        if (!isset($cache[$key])) {
            $ret = &Vilma_MailboxDriver::factory($driver, $params);
            if (is_a($ret, 'PEAR_Error')) {
                return $ret;
            }
            $cache[$key] = &$ret;
        }
        return $cache[$key];
    }

    /**
     * Creates a new mailbox.
     *
     * This default implementation only returns an error.
     *
     * @param string $user    The name of the mailbox to create
     * @param string $domain  The name of the domain in which to create the
     *                        mailbox
     * @return mixed  True or PEAR_Error:: instance.
     */
    function createMailbox($user, $domain)
    {
        return PEAR::raiseError(_("This driver cannot create mailboxes."));
    }

    /**
     * Deletes an existing mailbox.
     *
     * This default implementation only returns an error.
     *
     * @param string $user    The name of the mailbox to delete
     * @param string $domain  The name of the domain in which to delete the
     *                        mailbox
     *
     * @return mixed  True or PEAR_Error:: instance.
     */
    function deleteMailbox($user, $domain)
    {
        return PEAR::raiseError(_("This driver cannot delete mailboxes."));
    }

    /**
     * Checks whether a mailbox exists and is set up properly.
     *
     * @param string $user    The name of the mailbox to check
     * @param string $domain  The mailbox's domain
     *
     * @return mixed  True or PEAR_Error:: instance.
     */
    function checkMailbox($user, $domain)
    {
        return true;
    }

}
