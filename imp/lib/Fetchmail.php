<?php
/**
 * The IMP_Fetchmail:: class provides an interface to download mail from
 * remote mail servers.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Nuno Loureiro <nuno@co.sapo.pt>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
abstract class IMP_Fetchmail
{
    /**
     * Parameters used by the driver.
     *
     * @var array
     */
    protected $_params = array();

    /**
     * The list of active fetchmail parameters for the current driver.
     * ALL DRIVERS SHOULD UNSET ANY FETCHMAIL PARAMETERS THEY DO NOT USE
     * OR ELSE THEY WILL APPEAR IN THE PREFERENCES PAGE.
     * The following parameters are available:
     *   'id'          --  The account name.
     *   'driver'      --  The driver to use.
     *   'protocol'    --  The protocol type.
     *   'username'    --  The username on the remote server.
     *   'password'    --  The password on the remote server.
     *   'server'      --  The remote server name/address.
     *   'rmailbox'    --  The remote mailbox name.
     *   'lmailbox'    --  The local mailbox to download messages to.
     *   'onlynew'     --  Only retrieve new messages?
     *   'markseen'    --  Mark messages as seen?
     *   'del'         --  Delete messages after fetching?
     *   'loginfetch'  --  Fetch mail from other accounts on login?
     *   'acctcolor'   --  Should these messages be colored differently
     *                     in mailbox view?
     *
     * @var array
     */
    protected $_activeparams = array(
        'id', 'driver', 'type', 'protocol', 'username', 'password', 'server',
        'rmailbox', 'lmailbox', 'onlynew', 'markseen', 'del', 'loginfetch',
        'acctcolor'
    );

    /**
     * Attempts to return a concrete IMP_Fetchmail instance based on $driver.
     *
     * @param string $driver  The type of concrete IMP_Fetchmail subclass to
     *                        return, based on the driver indicated. The code
     *                        is dynamically included.
     *
     * @param array $params   The configuration parameter array.
     *
     * @return mixed  The newly created concrete IMP_Fetchmail instance, or
     *                false on error.
     */
    static public function factory($driver, $params = array())
    {
        $class = 'IMP_Fetchmail_' . basename($driver);
        return class_exists($class)
            ? new $class($params)
            : false;
    }

    /**
     * Returns a list of available drivers, with a description of each.
     * This function can be called statically:
     *   $list = IMP_Fetchmail::listDrivers();
     *
     * @return array  The list of available drivers, with the driver name as
     *                the key and the description as the value.
     */
    static public function listDrivers()
    {
        $drivers = array();

        if (($dir = opendir(dirname(__FILE__) . '/Fetchmail'))) {
            while (false !== ($file = readdir($dir))) {
                if (!is_dir($file)) {
                    $driver = basename($file, '.php');
                    $class = 'IMP_Fetchmail_' . $driver;
                    if (is_callable(array($class, 'description')) &&
                        ($descrip = call_user_func(array($class, 'description')))) {
                        $drivers[$driver] = $descrip;
                    }
                }
            }
            closedir($dir);
        }

        return $drivers;
    }

    /**
     * List the colors available for coloring fetched messages.
     * This function can be called statically:
     *   $list = IMP_Fetchmail::listColors();
     *
     * @return array  The list of available colors;
     */
    static public function listColors()
    {
        return array(
            'purple', 'lime', 'teal', 'blue', 'olive', 'fuchsia', 'navy',
            'aqua'
        );
    }

    /**
     * Returns a description of the driver.
     * This function can be called statically:
     *   $description = IMP_Fetchmail::description();
     *
     * @return string  The description of the driver.
     */
    abstract static public function description();

    /**
     * Perform fetchmail on the list of accounts given. Outputs informaton
     * to the global notification driver.
     * This function can be called statically.
     *
     * @param array $accounts  The list of account identifiers to fetch mail
     *                         for.
     */
    static public function fetchMail($accounts)
    {
        $fm_account = new IMP_Fetchmail_Account();

        foreach ($accounts as $val) {
            $params = $fm_account->getAllValues($val);
            $driver = IMP_Fetchmail::factory($params['driver'], $params);
            if ($driver === false) {
                continue;
            }
            $res = $driver->getMail();

            if (is_a($res, 'PEAR_Error')) {
                $GLOBALS['notification']->push(_("Fetchmail: ") . $res->getMessage(), 'horde.warning');
            } elseif ($res == 1) {
                $GLOBALS['notification']->push(_("Fetchmail: ") . sprintf(_("Fetched 1 message from %s"), $fm_account->getValue('id', $val)), 'horde.success');
            } elseif ($res >= 0) {
                $GLOBALS['notification']->push(_("Fetchmail: ") . sprintf(_("Fetched %d messages from %s"), $res, $fm_account->getValue('id', $val)), 'horde.success');
            } else {
                $GLOBALS['notification']->push(_("Fetchmail: no new messages."), 'horde.success');
            }
        }
    }

    /**
     * Constructor.
     *
     * @param array $params  The configuration parameter array.
     */
    public function __construct($params)
    {
        /* Check for missing params. */
        $paramlist = $this->getParameterList();
        if (array_diff($paramlist, array_keys($params))) {
            // TODO: Error message here
        }

        $this->_params = $params;
    }

    /**
     * Return the list of parameters valid for this driver.
     *
     * @return array  The list of active parameters.
     */
    public function getParameterList()
    {
        return $this->_activeparams;
    }

    /**
     * Return a list of protocols supported by this driver.
     *
     * @return array  The list of protocols.
     *                KEY: protocol ID
     *                VAL: protocol description
     */
    abstract public function getProtocolList();

    /**
     * Gets the mail using the data in this object.
     *
     * @return mixed  Returns the number of messages retrieved on success.
     *                Returns PEAR_Error on error.
     */
    abstract public function getMail();

    /**
     * Checks the message size to see if it exceeds the maximum value
     * allowable in the configuration file.
     *
     * @param integer $size    The size of the message.
     * @param string $subject  The subject of the message.
     * @param string $from     The message sender.
     *
     * @return boolean  False if message is too large, true if OK.
     */
    protected function _checkMessageSize($size, $subject, $from)
    {
        if (!empty($GLOBALS['conf']['fetchmail']['size_limit']) &&
            ($size > $GLOBALS['conf']['fetchmail']['size_limit'])) {
            $GLOBALS['notification']->push(sprintf(_("The message \"%s\" from \"%s\" (%d bytes) exceeds fetch size limit."), Horde_Mime::decode($subject), Horde_Mime::decode($from), $size), 'horde.warning');
            return false;
        }

        return true;
    }

    /**
     * Add the message to the requested local mailbox, performing any
     * necessary processing.
     *
     * @param string $header  The message header text.
     * @param string $body    The message body text.
     *
     * @return boolean  True on success, false on failure.
     */
    protected function _addMessage($header, $body)
    {
        $msg = rtrim($header);

        if (empty($this->_params['acctcolor'])) {
            $msg .= "\nX-color: " . $this->_params['acctcolor'];
        }
        $msg .= "\n\n" . $body;

        /* If there is a user defined function, call it with the current
         * message as an argument. */
        if ($GLOBALS['conf']['hooks']['fetchmail_filter']) {
            $msg = Horde::callHook('_imp_hook_fetchmail_filter', array($msg), 'imp');
        }

        try {
            $GLOBALS['imp_imap']->ob->append($this->_params['lmailbox'], array(array('data' => $msg)));
            return true;
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }
    }
}
