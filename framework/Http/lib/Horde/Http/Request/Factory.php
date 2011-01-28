<?php
/**
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Http
 */
class Horde_Http_Request_Factory
{
    /**
     * Find the best available request backend
     *
     * @return Horde_Http_Request_Base
     */
    public function create()
    {
        if (class_exists('HttpRequest', false)) {
            return new Horde_Http_Request_Peclhttp();
        } elseif (extension_loaded('curl')) {
            return new Horde_Http_Request_Curl();
        } elseif (ini_get('allow_url_fopen')) {
            return new Horde_Http_Request_Fopen();
        } else {
            throw new Horde_Http_Exception('No HTTP request backends are available. You must install pecl_http, curl, or enable allow_url_fopen.');
        }
    }
}
