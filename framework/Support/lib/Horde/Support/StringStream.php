<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Support
 */

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Support
 */
class Horde_Support_StringStream implements Horde_Stream_Wrapper_StringStream
{
    /**
     * @var string
     */
    protected $_string;

    /**
     * Constructor
     *
     * @param string &$string  Reference to the string to wrap as a stream
     */
    public function __construct(&$string)
    {
        $this->installWrapper();
        $this->_string =& $string;
    }

    /**
     * Return a stream handle to this string stream.
     *
     * @return resource
     */
    public function fopen()
    {
        $context = stream_context_create(array('horde-string' => array('string' => $this)));
        return fopen('horde-string://' . spl_object_hash($this), 'rb', false, $context);
    }

    /**
     * Return an SplFileObject representing this string stream
     *
     * @return SplFileObject
     */
    public function getFileObject()
    {
        $context = stream_context_create(array('horde-string' => array('string' => $this)));
        return new SplFileObject('horde-string://' . spl_object_hash($this), 'rb', false, $context);
    }

    /**
     * Install the horde-string stream wrapper if it isn't already registered.
     */
    public function installWrapper()
    {
        if (!in_array('horde-string', stream_get_wrappers())) {
            if (!stream_wrapper_register('horde-string', 'Horde_Stream_Wrapper_String')) {
                throw new Exception('Unable to register horde-string stream wrapper');
            }
        }
    }

    /**
     * Return a reference to the wrapped string.
     *
     * @return string
     */
    public function &getString()
    {
        return $this->_string;
    }
}
