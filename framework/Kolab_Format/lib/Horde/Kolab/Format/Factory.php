<?php
/**
 * A factory for generating Kolab format handlers.
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
 * A factory for generating Kolab format handlers.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Format_Factory
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Generates a handler for a specific Kolab object type.
     *
     * @param string $format The format that the handler should work with.
     * @param string $object The object type that should be handled.
     * @param array  $params Additional parameters.
     * <pre>
     * 'version' - The format version.
     * </pre>
     *
     * @return Horde_Kolab_Format The handler.
     *
     * @throws Horde_Kolab_Format_Exception If the specified handler does not
     *                                      exist.
     */
    public function create($format_type = '', $object_type = '', $params = null)
    {
        $class = 'Horde_Kolab_Format_' . ucfirst(strtolower($format_type)) . '_'
            . ucfirst(strtolower(str_replace('-', '', $object_type)));

        if (!isset($this->_instances[$class])) {
            if (class_exists($class)) {
                $this->_instances[$class] = new $class($params);
            } else {
                throw new Horde_Kolab_Format_Exception(
                    sprintf(
                        'Failed to load the specified Kolab Format handler (Class %s does not exist)!',
                        $class
                    )
                );
            }
        }
        return $this->_instances[$class];
    }

    /**
     * Generates a Kolab object handler with a timer wrapped around it..
     *
     * @param string $format The format that the handler should work with.
     * @param string $object The object type that should be handled.
     * @param array  $params Additional parameters.
     * <pre>
     * 'version' - The format version.
     * </pre>
     *
     * @return Horde_Kolab_Format The wrapped handler.
     *
     * @throws Horde_Kolab_Format_Exception If the specified handler does not
     *                                      exist.
     */
    public function createTimed($format_type = '', $object_type = '', $params = null)
    {
        if (isset($params['handler'])) {
            $handler = $params['handler'];
        } else {
            $handler = $this->create($format_type, $object_type, $params);
        }
        if (!class_exists('Horde_Support_Timer')) {
            throw new Horde_Kolab_Format_Exception('The Horde_Support package seems to be missing (Class Horde_Support_Timer is missing)!');
        }
        return new Horde_Kolab_Format_Decorator_Timed(
            $handler,
            new Horde_Support_Timer(),
            isset($params['logger']) ? $params['logger'] : null
        );
    }
}
