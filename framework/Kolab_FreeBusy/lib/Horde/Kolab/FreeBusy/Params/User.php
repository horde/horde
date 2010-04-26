<?php
/**
 * This class provides the credentials for the user currently accessing
 * the export system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class provides the credentials for the user currently accessing
 * the export system.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Params_User
{
    /**
     * The request made to the application.
     *
     * @var Horde_Controller_Request_Base
     */
    private $_request;

    /**
     * The user id.
     *
     * @var string
     */
    private $_user;

    /**
     * The user password.
     *
     * @var string
     */
    private $_pass;

    /**
     * Constructor.
     *
     * @param Horde_Controller_Request_Base $request The incoming request.
     */
    public function __construct(Horde_Controller_Request_Base $request)
    {
        $this->_request = $request;
    }

    /**
     * Return the user credentials extracted from the request.
     *
     * @return array The user credentials.
     */
    public function getCredentials()
    {
        if ($this->_user === null) {
            $this->_extractUserAndPassword();
        }
        return array($this->_user, $this->_pass);
    }

    /**
     * Return the user id.
     *
     * @return array The user id.
     */
    public function getId()
    {
        if ($this->_user === null) {
            $this->_extractUserAndPassword();
        }
        return $this->_user;
    }

    /**
     * Extract user name and password from the request.
     *
     * @return NULL
     */
    private function _extractUserAndPassword()
    {
        $this->_user = $this->_request->getServer('PHP_AUTH_USER');
        $this->_pass = $this->_request->getServer('PHP_AUTH_PW');

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
        if (empty($this->_user)) {
            $remote_user = $this->_request->getServer('REDIRECT_REDIRECT_REMOTE_USER');
            if (!empty($remote_user)) {
                $a = base64_decode(substr($remote_user, 6));
                if ((strlen($a) != 0) && (strcasecmp($a, ':') == 0)) {
                    list($this->_user, $this->_pass) = explode(':', $a, 2);
                }
            } else {
                $this->_user = '';
            }
        }
    }
}