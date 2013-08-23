<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Dynamically generate the password needed for the mail server connection.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Imap_Password implements Horde_Imap_Client_Base_Password, Serializable
{
    /* Password key. */
    const PASSWORD_KEY = 'imap_ob_pass';

    /**
     * Mail server password.
     *
     * @var string
     */
    private $_password;

    /**
     * Constructor.
     *
     * @param string $password  The mail server password.
     */
    public function __construct($password)
    {
        $this->_password = $password;
    }

    /* Horde_Imap_Client_Base_Password methods. */

    /**
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        global $session;

        $session->set('imp', self::PASSWORD_KEY, $this->_password, $session::ENCRYPT);

        return '';
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_password = $GLOBALS['session']->get('imp', self::PASSWORD_KEY);
    }

}
