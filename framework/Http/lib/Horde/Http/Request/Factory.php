<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Http
 */

/**
 * HTTP request object factory.
 *
 * Automatically determines the best suitable HTTP backend driver.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
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
        } elseif (class_exists('\http\Client', false)) {
            return new Horde_Http_Request_Peclhttp2();
        } elseif (extension_loaded('curl')) {
            return new Horde_Http_Request_Curl();
        } elseif (ini_get('allow_url_fopen')) {
            return new Horde_Http_Request_Fopen();
        } else {
            throw new Horde_Http_Exception('No HTTP request backends are available. You must install pecl_http, curl, or enable allow_url_fopen.');
        }
    }
}
