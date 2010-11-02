<?php
/**
 * Components_Pear_Package_Contents_Ignore:: indicates which files in
 * a content listing should be ignored.
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
 * Components_Pear_Package_Contents_Ignore:: indicates which files in
 * a content listing should be ignored.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Components_Pear_Package_Contents_Ignore
{
    /**
     * The gitignore information.
     *
     * @var string
     */
    private $_gitignore;

    /**
     * Constructor.
     *
     * @param string $gitignore The gitignore information
     */
    public function __construct($gitignore)
    {
        $this->_gitignore = $gitignore;
    }

    /**
     * Tell whether to ignore a file or a directory
     * allows * and ? wildcards
     *
     * @param string $file   just the file name of the file or directory,
     *                          in the case of directories this is the last dir
     * @param string $path   the full path
     * @param bool   $return value to return if regexp matches.  Set this to
     *                            false to include only matches, true to exclude
     *                            all matches
     *
     * @return bool  true if $path should be ignored, false if it should not
     * @access private
     */
    public function checkIgnore($file, $path, $return = 1)
    {
        return !$return;
    }
}