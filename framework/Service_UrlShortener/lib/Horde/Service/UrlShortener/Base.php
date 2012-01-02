<?php
/**
 * This file contains the Horde_Service_UrlShortener class for shortening URLs.
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
 * Horde_Service_UrlShortener Base class
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_UrlShortener
 */
 abstract class Horde_Service_UrlShortener_Base
 {
    /**
     * @var array
     */
    protected $_params;

    /**
     * @var Horde_Http_Client
     */
    protected $_http;

    /**
     * Constructor
     *
     * @param  Horde_Http_Client $http [description]
     * @param  array $params = array() [description]
     *
     * @return Horde_UrlShorten
     */
    public function __construct(Horde_Http_Client $http, $params = array())
    {
        $this->_http = $http;
        $this->_params = $params;
    }

    abstract public function shorten($url);
 }