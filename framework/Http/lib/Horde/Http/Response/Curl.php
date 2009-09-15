<?php
/**
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Http_Response_Curl extends Horde_Http_Response_Base
{
    /**
     * Info on the request
     * @var array
     */
    protected $_curlinfo = array();

    /**
     */
    public function __construct($curlresult, $curlinfo)
    {
        echo $curlresult;
        $this->parseResponse($curlresult, $curlinfo);
    }

    /**
     *
     */
    public function parseResponse($curlresult, $curlinfo)
    {
        $this->_curlinfo = $curlinfo;
    }

    /**
     * Return the body of the HTTP response.
     *
     * @return string HTTP response body.
     */
    public function getBody()
    {
        return $this->_body;
    }
}
