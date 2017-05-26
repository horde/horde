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
 * Mock HTTP response object.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
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
        $this->_parseHeaders($headers);
    }

    public function getBody()
    {
        $content = @stream_get_contents($this->_stream);
        if ($content === false) {
            $error = error_get_last();
            throw new Horde_Http_Exception('Problem reading data from ' . $this->uri . ': ' . $error['message']);
        }
        return $content;
    }
}
