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
class Horde_Http_Response_Mock extends Horde_Http_Response_Base
{
    /**
     * Constructor
     */
    public function __construct($uri, $stream, $headers = array())
    {
        $this->uri = $uri;
        $this->_stream = $stream;
        $this->headers = $this->_parseHeaders($headers);
    }

    public function getBody()
    {
        $content = @stream_get_contents($this->_stream);
        if ($content === false) {
            throw new Horde_Http_Exception('Problem reading data from ' . $this->uri . ': ' . $php_errormsg);
        }
        return $content;
    }
}
