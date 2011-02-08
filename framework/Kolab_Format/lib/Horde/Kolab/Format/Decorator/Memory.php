<?php
/**
 * Determines some memory parameters while loading/saving the Kolab objects.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Determines some memory parameters while loading/saving the Kolab objects.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
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
    public function __construct(
        Horde_Kolab_Format $handler,
        Horde_Support_Memory $memory,
        $logger = null
    ) {
        parent::__construct($handler);
        $this->_memory = $memory;
        $this->_logger = $logger;
    }

    /**
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load(&$xmltext)
    {
        $this->_memory->push();
        $result = $this->getHandler()->load($xmltext);
        $this->_logger->debug(
            sprintf(
                'Kolab Format data parsing complete. Memory usage: %s',
                $this->_formatUsage($this->_memory->pop())
            )
        );
        return $result;
    }

    /**
     * Convert the data to a XML string.
     *
     * @param array &$object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object)
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