<?php
/**
 * Determines some memory parameters while loading/saving the Kolab objects.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Determines some memory parameters while loading/saving the Kolab objects.
 *
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Decorator_Memory
extends Horde_Kolab_Format_Decorator_Base
{
    /**
     * The memory tracker used for recording the memory parameters.
     *
     * @var Horde_Support_Memory
     */
    private $_memory;

    /**
     * An optional logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Format   $handler The handler to be decorated.
     * @param Horde_Support_Memory $memory  The memory tracker.
     * @param mixed                $logger  The logger. This must provide
     *                                      a debug() method.
     */
    public function __construct(Horde_Kolab_Format $handler,
                                Horde_Support_Memory $memory,
                                $logger = null)
    {
        parent::__construct($handler);
        $this->_memory = $memory;
        $this->_logger = $logger;
    }

    /**
     * Load an object based on the given XML stream.
     *
     * @param resource $xml     The XML stream of the message.
     * @param array    $options Additional options when parsing the XML. This
     *                          decorator provides no additional options.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load($xml, $options = array())
    {
        $this->_memory->push();
        $result = $this->getHandler()->load($xml);
        $this->_logger->debug(
            sprintf(
                'Kolab Format data parsing complete. Memory usage: %s',
                $this->_formatUsage($this->_memory->pop())
            )
        );
        return $result;
    }

    /**
     * Convert the data to a XML stream.
     *
     * @param array $object The data array representing the object.
     * @param array $options Additional options when writing the XML. This
     *                       decorator provides no additional options.
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object, $options = array())
    {
        $this->_memory->push();
        $result = $this->getHandler()->save($object);
        $this->_logger->debug(
            sprintf(
                'Kolab Format data generation complete. Memory usage: %s',
                $this->_formatUsage($this->_memory->pop())
            )
        );
        return $result;
    }

    /**
     * Format the memory usage information.
     *
     * @param array $usage The memory usage.
     *
     * @return string The formated memory usage.
     */
    private function _formatUsage($usage)
    {
        return sprintf(
            '%.3f MB / %.3f MB / %.3f MB / %.3f MB [change in current usage (emalloc) / change in peak usage (emalloc) / change in current usage (real) / change in peak usage (real)]',
            $usage[0] / 1048576,
            $usage[1] / 1048576,
            $usage[2] / 1048576,
            $usage[3] / 1048576
        );
    }
}