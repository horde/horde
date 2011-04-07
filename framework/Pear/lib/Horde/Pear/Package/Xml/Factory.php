<?php
/**
 * Generates instances required for package.xml manipulations.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Generates instances required for package.xml manipulations.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml_Factory
{
    /**
     * Create an instance.
     *
     * @param string $type The instance type.
     *
     * @return mixed The instance.
     */
    public function create($type, $arguments)
    {
        switch ($type) {
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
}