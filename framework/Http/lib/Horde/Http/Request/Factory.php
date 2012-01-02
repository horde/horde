<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
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
