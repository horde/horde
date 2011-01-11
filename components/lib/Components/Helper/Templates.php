<?php
/**
 * Components_Helper_Templates:: converts templates into target files.
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
 * Components_Helper_Templates:: converts templates into target files.
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
class Components_Helper_Templates
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
     * @param string $sfile The exact template source file.
     * @param string $tfile The exact template target file.
     */
    public function __construct($sdir, $tdir, $sfile = '', $tfile = '')
    {
        $source = $sdir . DIRECTORY_SEPARATOR . $sfile . '.template';
        if (file_exists($source)) {
            $this->_source = $source;
        } else {
            throw new Components_Exception("No template at $source!");
        }
        $this->_target = $tdir . DIRECTORY_SEPARATOR . $tfile;
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
        $source = file_get_contents($this->_source);
        file_put_contents($this->_target, vsprintf($source, $parameters));
    }
}