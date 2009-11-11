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
 * This class represents the user accessing the free/bisy system.
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
class Horde_Kolab_FreeBusy_User
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
     * Are the provided user credentials valid?
     *
     * @var boolean
     */
    public $authenticated;

    /**
     * Constructor.
     *
     * @param Horde_Auth_Base  $auth   The authentication driver,
     * @param Horde_Log_Logger $logger The log driver.
     */
    public function __construct(Horde_Auth_Base $auth, Horde_Log_Logger $logger)
    {
        list($this->user, $this->pass) = $this->getCredentials();

        if (!empty($this->user)) {
            $this->authenticated = $auth->authenticate($this->user, array('password' => $pass), false);
            if ($this->authenticated) {
                $logger->notice(sprintf('Login success for %s [%s] to free/busy.', $this->user, $_SERVER['REMOTE_ADDR']));
            } else {
                $logger->err(sprintf('FAILED LOGIN for %s [%s] to free/busy', $this->user, $_SERVER['REMOTE_ADDR']));
            }
        } else {
            $logger->notice(sprintf('Anonymous login [%s] to free/busy.', $this->user, $_SERVER['REMOTE_ADDR']));
        }
    }

    /**
     * Create a new user object.
     *
     * @param Horde_Provider $provider The instance providing required
     *                                 dependencies.
     *
     * @return Horde_Kolab_FreeBusy_User The new user object.
     */
    static public function factory($provider)
    {
        $user = new Horde_Kolab_FreeBusy_User($provider->auth,
                                              $provider->logger);
        return $user;
    }

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
}