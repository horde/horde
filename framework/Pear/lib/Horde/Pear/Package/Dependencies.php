<?php
/**
 * Handles dependency conversions.
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
 * Handles dependency conversions.
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
class Horde_Pear_Package_Dependencies
{
    /**
     * Add a dependency.
     *
     * @param array  $input    The input array.
     * @param string $type     The dependency type.
     * @param string $optional Optional dependency or not?
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    static public function addDependency($input, $type, $optional, &$result)
    {
        switch ($type) {
        case 'php':
            self::addPhp($input, $result);
            break;
        case 'pearinstaller':
            self::addPear($input, $result);
            break;
        case 'package':
            self::addOther($input, 'pkg', $optional, $result);
            break;
        case 'extension':
            self::addOther($input, 'ext', $optional, $result);
            break;
        default:
            throw new Horde_Pear_Exception(
                sprintf('Unsupported dependency type "%s"!', $type)
            );
        }
    }

    /**
     * Add the PHP dependency.
     *
     * @param array  $input    The input array.
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    static public function addPhp($input, &$result)
    {
        $element = array(
            'type' => 'php',
            'optional' => 'no',
        );
        self::completeVersions($input, $element, $result);
    }

    /**
     * Add the PEAR dependency.
     *
     * @param array  $input    The input array.
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    static public function addPear($input, &$result)
    {
        $element = array(
            'type' => 'pkg',
            'name' => 'PEAR',
            'channel' => 'pear.php.net',
            'optional' => 'no',
        );
        self::completeVersions($input, $element, $result);
    }

    /**
     * Add a package dependency.
     *
     * @param array  $input    The input array.
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    static public function addOther($input, $type, $optional, &$result)
    {
        if (isset($input['conflicts'])) {
            return;
        }
        $element = $input;
        $element['type'] = $type;
        $element['optional'] = $optional;
        self::completeVersions($input, $element, $result);
    }

    /**
     * Parse version information.
     *
     * @param array  $input    The input array.
     * @param array  &$element The basic element information.
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    static public function completeVersions($input, &$element, &$result)
    {
        $added = false;
        if (self::_completeMin($input, $element)) {
            $result[] = $element;
            $added = true;
        }
        if (self::_completeMax($input, $element)) {
            $result[] = $element;
            $added = true;
        }
        if (!$added) {
            $result[] = $element;
        }
    }

    /**
     * Complete "min" version information.
     *
     * @param array  $input    The input array.
     * @param array  &$element The basic element information.
     *
     * @return boolean True if the was "min" information available.
     */
    static private function _completeMin($input, &$element)
    {
        if (isset($input['min'])) {
            $element['rel'] = 'ge';
            $element['version'] = $input['min'];
            return true;
        }
        return false;
    }

    /**
     * Complete "max" version information.
     *
     * @param array  $input    The input array.
     * @param array  &$element The basic element information.
     *
     * @return boolean True if the was "max" information available.
     */
    static private function _completeMax($input, &$element)
    {
        if (isset($input['max'])) {
            $element['rel'] = 'le';
            $element['version'] = $input['max'];
            return true;
        }
        return false;
    }
}