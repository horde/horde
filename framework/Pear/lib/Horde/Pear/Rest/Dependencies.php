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
        $this->_deps = unserialize($txt);
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