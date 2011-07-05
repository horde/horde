<?php
/**
 * A parser for a dependency list from a PEAR server.
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
 * A parser for a dependency list from a PEAR server.
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
class Horde_Pear_Rest_Dependencies
{
    /**
     * The dependency list.
     *
     * @var array
     */
    private $_deps;

    /**
     * Constructor.
     *
     * @param resource|string $txt The text document received from the server.
     */
    public function __construct($txt)
    {
        if (is_resource($txt)) {
            rewind($txt);
            $txt = stream_get_contents($txt);
        }
        if ($txt === false) {
            $this->_deps = array();
        } else {
            $deps = @unserialize($txt);
            if ($deps === false && $txt !== 'b:0;') {
                throw new Horde_Pear_Exception(
                    sprintf('Unable to parse dependency response "%s"!', $txt)
                );
            }
            $result = array();
            if (isset($deps['required'])) {
                foreach ($deps['required'] as $type => $required) {
                    $this->_convert($type, $required, 'no', $result);
                }
            }
            if (isset($deps['optional'])) {
                foreach ($deps['optional'] as $type => $optional) {
                    $this->_convert($type, $optional, 'no', $result);
                }
            }
            $this->_deps = $result;
        }
    }

    /**
     * Convert the PEAR server response into an array that we would get when
     * accessing the dependencies of a local package.xml via PEAR.
     *
     * @param string $type     The dependency type.
     * @param array  $input    The input array.
     * @param string $optional Indicates if it is an optional dependency.
     * @param array  &$result  The result array.
     *
     * @return NULL
     */
    private function _convert($type, $input, $optional, &$result)
    {
        if ($type == 'php') {
            $element = array(
                'type' => $type,
                'optional' => 'no',
            );
            $this->_completeVersions($input, $element, $result);
        } else if ($type == 'pearinstaller') {
            $element = array(
                'type' => 'pkg',
                'name' => 'PEAR',
                'channel' => 'pear.php.net',
                'optional' => 'no',
            );
            $this->_completeVersions($input, $element, $result);
        } else if ($type == 'package') {
            if (isset($input['name'])) {
                $element = $input;
                $element['type'] = 'pkg';
                $this->_completeVersions($input, $element, $result);
            } else {
                foreach ($input as $pkg) {
                    $element = $pkg;
                    $element['type'] = 'pkg';
                    $this->_completeVersions($pkg, $element, $result);
                }
            }
        } else if ($type == 'extension') {
            if (isset($input['name'])) {
                $element = $input;
                $element['type'] = 'ext';
                $this->_completeVersions($input, $element, $result);
            } else {
                foreach ($input as $ext) {
                    $element = $ext;
                    $element['type'] = 'ext';
                    $this->_completeVersions($ext, $element, $result);
                }
            }
        } else {
            throw new Horde_Pear_Exception(
                sprintf('Unsupported dependency type "%s"!', $type)
            );
        }
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
    private function _completeVersions($input, &$element, &$result)
    {
        $added = false;
        if ($added |= $this->_completeMin($input, $element)) {
            $result[] = $element;
        }
        if ($added |= $this->_completeMax($input, $element)) {
            $result[] = $element;
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
    private function _completeMin($input, &$element)
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
    private function _completeMax($input, &$element)
    {
        if (isset($input['max'])) {
            $element['rel'] = 'le';
            $element['version'] = $input['max'];
            return true;
        }
        return false;
    }

    /**
     * Return the package name.
     *
     * @return string The package name.
     */
    public function getDependencies()
    {
        return $this->_deps;
    }
}