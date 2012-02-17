<?php
/**
 * Components_Helper_Templates_RecursiveDirectory:: converts template files
 * recursively from a directory into files in a target directory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Templates_RecursiveDirectory:: converts template files
 * recursively from a directory into files in a target directory.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_Templates_RecursiveDirectory
extends Components_Helper_Templates
{
    /**
     * The source location.
     *
     * @var string
     */
    private $_source;

    /**
     * The target location.
     *
     * @var string
     */
    private $_target;

    /**
     * Constructor.
     *
     * @param string $sdir  The templates source directory.
     * @param string $tdir  The templates target directory.
     */
    public function __construct($sdir, $tdir)
    {
        if (file_exists($sdir)) {
            $this->_source = $sdir;
        } else {
            throw new Components_Exception("No template directory at $sdir!");
        }
        $this->_target = $tdir;
    }

    /**
     * Rewrite the template(s) from the source(s) to the target location(s).
     *
     * @param array  $parameters The template(s) parameters.
     *
     * @return NULL
     */
    public function write(array $parameters = array())
    {
        if (!file_exists($this->_target)) {
            mkdir($this->_target, 0777, true);
        }
        foreach (
            new IteratorIterator(new DirectoryIterator($this->_source))
            as $file
        ) {
            if ($file->isFile()) {
                $this->writeSourceToTarget(
                    $file->getPathname(),
                    $this->_target . '/' . $file->getBasename(),
                    $parameters
                );
            }
            if ($file->isDir() && !$file->isDot()) {
                $directory = new Components_Helper_Templates_RecursiveDirectory(
                    $this->_source . '/' . $file->getBasename(),
                    $this->_target . '/' . $file->getBasename()
                );
                $directory->write($parameters);
            }
        }
    }
}