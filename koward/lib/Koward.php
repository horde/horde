<?php
/**
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @category Kolab
 * @package  Koward
 */

class Koward_Koward {

    /**
     * The singleton instance.
     *
     * @var Koward_Koward
     */
    static protected $instance = null;

    public $objectconf;

    public function __construct()
    {
        require_once 'Horde/Notification.php';
        require_once 'Horde/Registry.php';

        $this->notification = Notification::singleton();
        $this->registry     = Registry::singleton();

        $result = $this->registry->pushApp('koward', false);
        if ($result instanceOf PEAR_Error) {
            $this->notification->push($result);
        }

        $this->conf       = Horde::loadConfiguration('conf.php', 'conf');
        $this->objects    = Horde::loadConfiguration('objects.php', 'objects');
        $this->attributes = Horde::loadConfiguration('attributes.php', 'attributes');
        $this->server     = Horde_Kolab_Server::singleton();
    }

    /**
     * Get a token for protecting a form.
     *
     * @param string $seed  TODO
     *
     * @return  TODO
     */
    static public function getRequestToken($seed)
    {
        $token = Horde_Token::generateId($seed);
        $_SESSION['horde_form_secrets'][$token] = time();
        return $token;
    }

    /**
     * Check if a token for a form is valid.
     *
     * @param string $seed   TODO
     * @param string $token  TODO
     *
     * @throws Horde_Exception
     */
    static public function checkRequestToken($seed, $token)
    {
        if (empty($_SESSION['horde_form_secrets'][$token])) {
            throw new Horde_Exception(_("We cannot verify that this request was really sent by you. It could be a malicious request. If you intended to perform this action, you can retry it now."));
        }

        if ($_SESSION['horde_form_secrets'][$token] + $GLOBALS['conf']['server']['token_lifetime'] < time()) {
            throw new Horde_Exception(sprintf(_("This request cannot be completed because the link you followed or the form you submitted was only valid for %d minutes. Please try again now."), round($GLOBALS['conf']['server']['token_lifetime'] / 60)));
        }
    }

    public function getObject($uid)
    {
        return $this->server->fetch($uid);
    }

    static public function singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Koward_Koward();
        }

        return self::$instance;
    }
}