<?php
/**
 * Horde_Serivce_Vimeo:: wrapper around Vimeo's (http://www.vimeo.com)
 * API.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */
class Horde_Service_Vimeo {

    /**
     * The format of the data returned from Vimeo.
     * Obviously does not apply to the getEmbedJson() method.
     *
     * php  - serialized php array
     * json - json encoded
     *
     * @var string
     */
    protected $_format = 'php';

    /**
     * HTTP client object to use for accessing the Vimeo API.
     * @var Horde_Http_Client
     */
    protected $_http_client;

    /**
     * An optional cache object
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * The lifetime of any cached data in seconds.
     *
     * @var int
     */
    protected $_cache_lifetime = 60;


    /**
     * Setter for changing the format parameter
     *
     * @param string $format  The data format requested.
     */
    public function setFormat($format)
    {
        $this->_format = $format;
    }

    /**
     * Facory method. Attempt to return a concrete Horde_Service_Vimeo instance
     * based on the parameters. A 'http_client' parameter is required. An
     * optional 'cache' and 'cache_lifetime' parameters are also taken.
     *
     * @param string $driver  The concrete class to instantiate.
     * @param array $params   An array containing any parameters the class needs.
     *
     * @return Horde_Service_Vimeo object
     */
    public static function factory($driver = 'Simple', $params = null)
    {

        // Check for required dependencies
        if (empty($params['http_client'])) {
            // Throw exception
        }

        $driver = basename($driver);

        include_once dirname(__FILE__) . '/Vimeo/' . $driver . '.php';
        $class = 'Horde_Service_Vimeo_' . $driver;
        if (class_exists($class)) {
            return new $class($params['http_client'], $params);
        } else {
            // @TODO: Exceptions!!!
            Horde::fatal(PEAR::raiseError(sprintf(_("Unable to load the definition of %s."), $class)), __FILE__, __LINE__);
        }
    }

    /**
     * Constructor
     *
     * @param Horde_Http_Client $http_client  Http client object.
     * @param array $params                   An array of any other parameters
     *                                        or optional object dependencies.
     *
     * @return Horde_Service_Vimeo object
     */
    private function __construct($http_client, $params)
    {
        $this->_http_client = $http_client;

        if (isset($params['cache'])) {
            $this->_cache = $params['cache'];
            if (isset($params['cache_lifetime'])) {
                $this->_cache_lifetime = $params['cache_lifetime'];
            }
        }
    }

}