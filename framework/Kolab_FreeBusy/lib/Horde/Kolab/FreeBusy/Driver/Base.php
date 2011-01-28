<?php
/**
 * The Kolab implementation of the free/busy system.
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
 * The Horde_Kolab_FreeBusy class serves as Registry aka ServiceLocator for the
 * Free/Busy application. It also provides the entry point into the the Horde
 * MVC system and allows to dispatch a request.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
 */
class Horde_Kolab_FreeBusy_Driver_Base
{
    /**
     * The user calling the script.
     *
     * @var string
     */
    protected $user;

    /**
     * The password of the user calling the script.
     *
     * @var string
     */
    protected $pass;

    /**
     * The logging handler.
     *
     * @var Horde_Log_Logger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param array       $params        Any additional options
     */
    public function __construct($callee = null, $callee_part = null,
                                $logger = null)
    {
        list($this->user, $this->pass) = $this->getCredentials();

        if (!empty($this->user)) {
            $this->authenticate();
        }

        if (!empty($callee)) {
            list($this->callee, $this->remote) = $this->handleCallee($callee);
        }
        if (!empty($callee_part)) {
            list($this->callee, $this->remote, $this->part) = $this->handleCallee($callee_part);
        }

        $this->logger = $logger;
    }

    /**
     * Create a new driver.
     *
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Kolab_FreeBusy_Driver_Base The new driver.
     */
    static public function factory($provider)
    {
        $class       = 'Horde_Kolab_FreeBusy_Driver_Freebusy_Kolab';
        $callee      = isset($provider->callee) ? $provider->callee : null;
        $callee_part = isset($provider->callee_part) ? $provider->callee_part : null;
        $driver      = new $class($callee, $callee_part, $provider->logger);
        return $driver;
    }

    /**
     * Parse the current user accessing the page and try to
     * authenticate the user.
     */
    protected function getCredentials()
    {
        $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : false;
        $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : false;

        //@todo: Fix!
        // This part allows you to use the PHP scripts with CGI rather than as
        // an apache module. This will of course slow down things but on the
        // other hand it allows you to reduce the memory footprint of the
        // apache server. The default is to use PHP as a module and the CGI
        // version requires specific Apache configuration.
        //
        // The line you need to add to your configuration of the /freebusy
        // location of your server looks like this:
        //
        //    RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]
        //
        // The complete section will probably look like this then:
        //
        //  <IfModule mod_rewrite.c>
        //    RewriteEngine On
        //    # FreeBusy list handling
        //    RewriteBase /freebusy
        //    RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization}]
        //    RewriteRule ^([^/]+)\.ifb       freebusy.php?uid=$1		    [L]
        //    RewriteRule ^([^/]+)\.vfb       freebusy.php?uid=$1		    [L]
        //    RewriteRule ^([^/]+)\.xfb       freebusy.php?uid=$1&extended=1        [L]
        //    RewriteRule ^trigger/(.+)\.pfb  pfb.php?folder=$1&cache=0             [L]
        //    RewriteRule ^(.+)\.pfb          pfb.php?folder=$1&cache=1             [L]
        //    RewriteRule ^(.+)\.pxfb         pfb.php?folder=$1&cache=1&extended=1  [L]
        //  </IfModule>
        if (empty($user) && isset($_ENV['REDIRECT_REDIRECT_REMOTE_USER'])) {
            $a = base64_decode(substr($_ENV['REDIRECT_REDIRECT_REMOTE_USER'], 6)) ;
            if ((strlen($a) != 0) && (strcasecmp($a, ':') == 0)) {
                list($user, $pass) = explode(':', $a, 2);
            }
        }
        return array($user, $pass);
    }

    /**
     * Authenticate
     *
     * @return boolean|PEAR_Error True if successful.
     */
    public function authenticate()
    {
            /* Load the authentication libraries */
            require_once 'Horde/Auth.php';
            require_once 'Horde/Secret.php';

            $auth = &Auth::singleton(isset($conf['auth']['driver'])?$conf['auth']['driver']:'kolab');
            if (!$this->_authenticated) {
                $this->_authenticated = $auth->authenticate($this->user, array('password' => $pass), false);
            }
            if ($this->_authenticated) {
                @session_start();
                $_SESSION['__auth'] = array(
                    'authenticated' => true,
                    'userId' => $this->user,
                    'timestamp' => time(),
                    'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
                );
                $GLOBALS['registry']->setAuthCredential('password', $pass);
            }
    }

    /**
     * Check if we are in an authenticated situation.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    public function authenticated()
    {
        global $conf;

        if (empty($this->user)) {
            header('WWW-Authenticate: Basic realm="Kolab Freebusy"');
            return PEAR::raiseError(Horde_Kolab_FreeBusy_Translation::t("Please authenticate!"));
        }

        if (!$this->_authenticated) {
            return PEAR::raiseError(sprintf(Horde_Kolab_FreeBusy_Translation::t("Invalid authentication for user %s!"),
                                            $this->user));
        }
        return true;
    }

    /**
     * Fetch the data.
     *
     * @params array $params Additional options.
     *
     * @return array The fetched data.
     */
    //abstract public function fetch($params = array());
}
