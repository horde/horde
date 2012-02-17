<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Http
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
            $oldTrackErrors = ini_set('track_errors', 1);
            $content = @stream_get_contents($this->_stream);
            ini_set('track_errors', $oldTrackErrors);
            if ($content === false) {
                $msg = 'Problem reading data from ' . $this->uri;
                if (isset($php_errormsg)) {
                    $msg .= ': ' . $php_errormsg;
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
