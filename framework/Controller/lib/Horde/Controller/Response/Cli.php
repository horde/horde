<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */

/**
 * @TODO Allow specifying the stream where output is going instead of assuming
 * STDOUT.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Controller
 * @subpackage Response
 */
class Horde_Controller_Response_Cli extends Horde_Controller_Response_Base
{
    /**
     * @var stream
     */
    protected $_stream;

    public function construct()
    {
        $this->_stream = fopen('php://stdout');
    }

    /**
     * Writes a string to the Response stream
     *
     * Can be called with an array of parameters or with a variable number of
     * parameters like printf.
     *
     * @param string $string The string to write to the reponse stream
     * @param array|params $params The parameters to replace in the string (think printf)
     */
    public function write($string, $params = null)
    {
        if (!is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        fwrite($this->_stream, vsprintf($string, $params));
    }

    /**
     * Writes a newline-terminated string to the Response stream
     *
     * Can be called with an array of parameters or with a variable number of
     * parameters like printf.
     *
     * @param string $string The string to write to the reponse stream
     * @param array|params $params The parameters to replace in the string (think printf)
     */
    public function writeLn($string, $params = array())
    {
        if (!is_array($params)) {
            $params = func_get_args();
            array_shift($params);
        }
        $line = vsprintf($string, $params);
        if (substr($line, -1) != "\n") {
            $line .= "\n";
        }
        fwrite($this->_stream, $line);
    }
}
