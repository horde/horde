<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Object representation of a remote mail account.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property string $hostspec  Remote host.
 * @property-read string $id  Remote account storage ID.
 * @property-read IMP_Imap_Remote $imp_imap  IMP IMAP object.
 * @property string $label  Remote account label.
 * @property integer $port  Remote server port.
 * @property mixed $secure  See backends.php ('secure' parameter).
 * @property integer $type  The connection type (self::IMAP or self::POP3).
 * @property string $username  Remote username.
 */
class IMP_Remote_Account implements Serializable
{
    /* Constants used for the 'type' property. */
    const IMAP = 1;
    const POP3 = 2;

    /* Return values for login(). */
    const LOGIN_BAD = 0;
    const LOGIN_BAD_CHANGED = 1;
    const LOGIN_OK = 2;
    const LOGIN_OK_CHANGED = 3;

    /**
     * Configuration.
     *
     * @var array
     */
    protected $_config = array();

    /**
     */
    public function __construct()
    {
        $this->_config['id'] = strval(new Horde_Support_Randomid());
    }

    /**
     * String representation of object.
     *
     * @return string  The identifier (mailbox) ID.
     */
    public function __toString()
    {
        return IMP_Remote::MBOX_PREFIX . $this->_config['id'];
    }

    /**
     */
    public function __get($name)
    {
        if (isset($this->_config[$name])) {
            return $this->_config[$name];
        }

        switch ($name) {
        case 'hostspec':
            return 'localhost';

        case 'imp_imap':
            return $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create(strval($this));

        case 'label':
            return $this->hostspec;

        case 'port':
            return ($this->type == self::POP3) ? 110 : 143;

        case 'secure':
            return null;

        case 'type':
            return self::IMAP;

        case 'username':
            return '';
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'hostspec':
        case 'label':
        case 'username':
            $this->_config[$name] = strval($value);
            break;

        case 'port':
        case 'type':
            $this->_config[$name] = intval($value);
            break;

        case 'secure':
            $this->_config[$name] = $value;
            break;
        }
    }

    /**
     * Attempt to login to remote account.
     *
     * @param string $password   The password to use. If null, attempts to use
     *                           the encrypted password stored in the config.
     * @param boolean $save      If true, save the password (encrypted) to the
     *                           config.
     *
     * @return integer  One of the LOGIN_* constants.
     */
    public function login($password = null, $save = false)
    {
        global $injector, $registry;

        if ($this->imp_imap->init) {
            return self::LOGIN_OK;
        }

        $blowfish_params = array(
            'cipher' => 'cbc',
            /* PBKDF2 is already using a salt, so no need to use yet another
             * salt (IV) also. */
            'iv' => str_repeat("\0", Horde_Crypt_Blowfish::IV_LENGTH)
        );

        if (is_null($password)) {
            if (!isset($this->_config['password_save'])) {
                return self::LOGIN_BAD;
            }

            /* Use the user's current password as the key, after using
             * PBKDF2 for key lengthening. This means that stored passwords
             * will be invalidated anytime the "master" password is
             * changed, but that is ok (not really another option). */
            list($salt, $pass) = explode(
                "\0",
                base64_decode($this->_config['password_save']),
                2
            );
            $blowfish = new Horde_Crypt_Blowfish(
                strval(new Horde_Crypt_Blowfish_Pbkdf2(
                    $registry->getAuthCredential('password'),
                    24,
                    array('salt' => $salt)
                )),
                $blowfish_params
            );

            $password = $blowfish->decrypt($pass);
        }

        $this->imp_imap->createImapObject(array(
            'hostspec' => $this->hostspec,
            'password' => new IMP_Imap_Password($password),
            'port' => $this->port,
            'secure' => $this->secure,
            'username' => $this->username,
        ), $this->type == self::IMAP, strval($this));

        try {
            $this->imp_imap->login();
        } catch (IMP_Imap_Exception $e) {
            $injector->getInstance('IMP_Factory_Imap')->destroy(strval($this));
            if (!isset($this->_config['password_save'])) {
                return self::LOGIN_BAD;
            }
            unset($this->_config['password_save']);
            return self::LOGIN_BAD_CHANGED;
        }

        if (!$save || isset($this->_config['password_save'])) {
            return self::LOGIN_OK;
        }

        $pbkdf2 = new Horde_Crypt_Blowfish_Pbkdf2(
            $registry->getAuthCredential('password'),
            24
        );
        $blowfish = new Horde_Crypt_Blowfish(
            strval($pbkdf2),
            $blowfish_params
        );

        /* Storage details (password_save):
         *   - PBKDF2 Salt
         *   - "\0"
         *   - Encrypted data (Remote account password) */
        $this->_config['password_save'] = base64_encode(implode(
            "\0",
            array($pbkdf2->salt, $blowfish->encrypt($password))
        ));

        return self::LOGIN_OK_CHANGED;
    }

    /**
     * Return mailbox name.
     *
     * @param string $id  Base IMAP name.
     *
     * @return string  IMP mailbox name.
     */
    public function mailbox($id)
    {
        return strval($this) . "\0" . $id;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode($this->_config);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_config = json_decode($data, true);
    }

}
