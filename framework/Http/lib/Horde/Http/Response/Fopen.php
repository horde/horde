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
 * HTTP response object for the fopen backend.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Http
 */
class Horde_Http_Response_Fopen extends Horde_Http_Response_Base
{
    /**
     * Response body.
     *
     * @var stream
     */
    protected $_stream;

    /**
     * Response content
     */
    protected $_content;

    /**
     * Constructor.
     */
    public function __construct($uri, $stream, $headers = array())
    {
        $this->uri = $uri;
        $this->_stream = $stream;
        $this->_parseHeaders($headers);
    }

    /**
     * Returns the body of the HTTP response.
     *
     * @throws Horde_Http_Exception
     * @return string HTTP response body.
     */
    public function getBody()
    {
        if (is_null($this->_content)) {
            $content = @stream_get_contents($this->_stream);
            if ($content === false) {
                $msg = 'Problem reading data from ' . $this->uri;
                if ($error = error_get_last()) {
                    $msg .= ': ' . $error['message'];
                }
                throw new Horde_Http_Exception($msg);
            }
            $this->_content = $content;
        }

        return $this->_content;
    }

    /**
     * Returns a stream pointing to the response body that can be used with
     * all standard PHP stream functions.
     */
    public function getStream()
    {
        return $this->_stream;
    }
}
