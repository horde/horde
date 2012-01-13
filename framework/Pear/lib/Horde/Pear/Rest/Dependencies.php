<?php
/**
 * A parser for a dependency list from a PEAR server.
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
 * A parser for a dependency list from a PEAR server.
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
                    $this->_convert($type, $optional, 'yes', $result);
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
        if (in_array($type, array('package', 'extension'))
            && !isset($input['name'])) {
            foreach ($input as $element) {
                $this->_convert($type, $element, $optional, $result);
            }
        } else {
            Horde_Pear_Package_Dependencies::addDependency(
                $input, $type, $optional, $result
            );
        }

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