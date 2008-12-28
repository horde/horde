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

    /**
     * Get the raw JSON response containing the data to embed a single video.
     *
     * @param mixed $optons  Either an array containing api parameters or the
     *                       video id. If an array, if the url is not passed,
     *                       we find it from the video_id.
     * Parameters:
     * url OR video_id
     * width
     * maxwidth
     * byline
     * title
     * portrait
     * color
     * callback
     *
     * @return JSON encoded data
     */
    public function getEmbedJSON($options)
    {
        if (!is_array($options)) {
            // Assume it's a video id, need to get the video url
            // @TODO
        }

        // $options should be an array now
        if (empty($options['url']) && !empty($options['video_id'])) {
            // We were originally passed an array, but still need the url
            // @TODO
        }

        // We should have a url now, and possibly other options.
        $url = Util::addParameter($this->_oembed_endpoint, $options, null, false);

        $req = $this->getHttpClient();
        $response = $req->request('GET', $url);
        $results = $response->getBody();

        return $results;
    }

    /**
     */
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