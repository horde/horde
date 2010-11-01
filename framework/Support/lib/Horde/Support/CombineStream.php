<?php
/**
 * Provides access to the Combine stream wrapper.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Support
 */

/**
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package  Horde_Support
 */
class Horde_Support_CombineStream implements Horde_Stream_Wrapper_CombineStream
{
    /**
     * Data.
     *
     * @var array
     */
    protected $_data;

    /**
     * Constructor
     *
     * @param array $data  An array of strings and/or streams to combine into
     *                     a single stream.
     */
    public function __construct($data)
    {
        $this->installWrapper();
        $this->_data = $data;
    }

    /**
     * Return a stream handle to this stream.
     *
     * @return resource
     */
    public function fopen()
    {
        $context = stream_context_create(array('horde-combine' => array('data' => $this)));
        return fopen('horde-combine://' . spl_object_hash($this), 'rb', false, $context);
    }

    /**
     * Return an SplFileObject representing this stream
     *
     * @return SplFileObject
     */
    public function getFileObject()
    {
        $context = stream_context_create(array('horde-combine' => array('data' => $this)));
        return new SplFileObject('horde-combine://' . spl_object_hash($this), 'rb', false, $context);
    }

    /**
     * Install the horde-combine stream wrapper if it isn't already
     * registered.
     *
     * @throws Exception
     */
    public function installWrapper()
    {
        if (!in_array('horde-combine', stream_get_wrappers()) &&
            !stream_wrapper_register('horde-combine', 'Horde_Stream_Wrapper_Combine')) {
            throw new Exception('Unable to register horde-combine stream wrapper.');
        }
    }

    /**
     * Return a reference to the data.
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

}
