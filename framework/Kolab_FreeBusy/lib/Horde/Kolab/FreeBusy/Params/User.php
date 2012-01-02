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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class provides the credentials for the user currently accessing
 * the export system.
 *
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Params_User
{
    /**
     * The request.
     *
     * @var Horde_Controller_Request
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
     * @param Horde_Controller_Request $request The request.
     */
    public function __construct(Horde_Controller_Request $request)
    {
        $this->_request = $request;
        $this->_extractUserAndPassword();
    }

    /**
     * Return the user credentials extracted from the request.
     *
     * @return array The user credentials.
     */
    public function getCredentials()
    {
        return array($this->_user, $this->_pass);
    }

    /**
     * Return the user id.
     *
     * @return array The user id.
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * Extract user name and password from the request.
     *
     * @return NULL
     */
    private function _extractUserAndPassword()
    {
        $vars = $this->_request->getServerVars();
        $this->_user = isset($vars['PHP_AUTH_USER']) ? $vars['PHP_AUTH_USER'] : null;
        $this->_pass = isset($vars['PHP_AUTH_PW']) ? $vars['PHP_AUTH_PW'] : null;

        // This part allows you to use the PHP scripts with CGI rather than as
        // an apache module. This will of course slow down things but on the
        // other hand it allows you to reduce the memory footprint of the 
        // apache server. The default is to use PHP as a module and the CGI 
        // version requires specific Apache configuration.
        //
        // http://www.besthostratings.com/articles/http-auth-php-cgi.html
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
            $remote_user = isset($vars['REDIRECT_REDIRECT_REMOTE_USER']) ? $vars['REDIRECT_REDIRECT_REMOTE_USER'] : null;
            if (!empty($remote_user)) {
                $a = base64_decode(substr($remote_user, 6));
                if (strlen($a) > 0 && strpos($a, ':') !== false) {
                    list($this->_user, $this->_pass) = explode(':', $a, 2);
                }
            } else {
                $this->_user = '';
            }
        }
    }
}