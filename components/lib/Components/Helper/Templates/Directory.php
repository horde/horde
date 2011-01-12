<?php
/**
 * Components_Helper_Templates_Directory:: converts template files from a
 * directory into files in a target directory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Helper_Templates_Directory:: converts template files from a
 * directory into files in a target directory.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Helper_Templates_Directory
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
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->_source)
            )
            as $file
        ) {
            if ($file->isFile()) {
                $this->writeSourceToTarget(
                    $file->getPathname(),
                    $this->_target . DIRECTORY_SEPARATOR . $file->getBasename(),
                    $parameters
                );
            }
        }
    }
}