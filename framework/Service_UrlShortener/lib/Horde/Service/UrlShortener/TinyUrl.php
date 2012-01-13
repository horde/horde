<?php
/**
 * This file contains the Horde_Service_UrlShortener class for shortening URLs
 * using the TinyUrl service.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category Horde
 * @package  Service_UrlShortener
 */

/**
 * UrlShortener class for TinyUrl.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_UrlShortener
 */
class Horde_Service_UrlShortener_TinyUrl extends Horde_Service_UrlShortener_Base
{
    const API_URL = 'http://tinyurl.com/api-create.php';

    /**
     *
     * @param  string $url The URL to shorten
     *
     * @return string  The shortened URL
     * @throws Horde_UrlShorten_Exception
     */
    public function shorten($url)
    {
        $u = new Horde_Url(self::API_URL);
        $u = $u->setRaw(true)->add('url', $url);
        try {
            $response = $this->_http->get($u);
        } catch (Horde_Http_Exception $e) {
            throw new Horde_Service_UrlShortener_Exception($e);
        }

        return $response->getBody();
    }

}