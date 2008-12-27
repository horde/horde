<?php

/** HTTP_Request **/
require_once 'HTTP/Request.php';

/**
 * Horde_Serivce_Vimeo:: wrapper around Vimeo's (http://www.vimeo.com)
 * API.
 *
 * Copyright 2008 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Service_Vimeo {

    protected $_format = 'php';

    /**
     * HTTP client object to use for accessing the Vimeo API.
     * @var Horde_Http_Client
     */
    protected static $_httpClient = null;

    /**
     * Set the HTTP client instance
     *
     * Sets the HTTP client object to use for Vimeo requests. If none is set,
     * the default Horde_Http_Client will be used.
     *
     * @param Horde_Http_Client $httpClient
     */
    public static function setHttpClient($httpClient)
    {
        self::$_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return Horde_Http_Client
     */
    public static function getHttpClient()
    {
        if (!self::$_httpClient) {
            self::$_httpClient = new Horde_Http_Client;
        }

        return self::$_httpClient;
    }


    public function factory($driver = 'Simple')
    {
        $driver = basename($driver);

        include_once dirname(__FILE__) . '/Vimeo/' . $driver . '.php';
        $class = 'Horde_Service_Vimeo_' . $driver;
        if (class_exists($class)) {
            return new $class($params);
        } else {
            // @TODO: Exceptions!!!
            Horde::fatal(PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class)), __FILE__, __LINE__);
        }
    }

}