<?php
/**
 * Generates instances required for package.xml manipulations.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Generates instances required for package.xml manipulations.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml_Factory
{
    /**
     * Create an instance.
     *
     * @param string $type      The instance type.
     * @param array  $arguments The constructor arguments.
     *
     * @return mixed The instance.
     */
    public function create($type, $arguments)
    {
        switch ($type) {
        case 'Contents':
            $class = 'Horde_Pear_Package_Xml_Contents';
            break;
        case 'Directory':
            $class = 'Horde_Pear_Package_Xml_Directory';
            break;
        case 'ElementDirectory':
            $class = 'Horde_Pear_Package_Xml_Element_Directory';
            break;
        case 'ElementFile':
            $class = 'Horde_Pear_Package_Xml_Element_File';
            break;
        default:
            throw new InvalidArgumentException(
                sprintf('Cannot create instance for %s!', $type)
            );
        }
        $reflectionObj = new ReflectionClass($class);
        return $reflectionObj->newInstanceArgs($arguments);
    }

    /**
     * Create a task handler.
     *
     * @param string $type      The task type.
     * @param array  $arguments The constructor arguments.
     *
     * @return Horde_Pear_Package_Task The task instance.
     */
    public function createTask($type, $arguments)
    {
        $class = 'Horde_Pear_Package_Task_' . ucfirst($type);
        if (class_exists($class)) {
            $reflectionObj = new ReflectionClass($class);
            return $reflectionObj->newInstanceArgs($arguments);
        } else {
            throw new InvalidArgumentException(sprintf('No task %s!', $type));
        }
    }
}