<?php
/**
 * Components_Pear_Package_Contents_List:: is the default content handler for
 * packages.
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
 * Components_Pear_Package_Contents_List:: is the default content handler for
 * packages.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Components_Pear_Package_Contents_List
extends PEAR_PackageFileManager_File
{
    /**
     * Determines if a file should be ignored.
     *
     * @var Components_Pear_Package_Contents_Ignore
     */
    private $_ignore;

    /**
     * Constructor.
     *
     * @param string                                  $package_directory The directory of the package.
     * @param Components_Pear_Package_Contents_Ignore $ignore            Ignores files.
     */
    public function __construct(
        $package_directory, Components_Pear_Package_Contents_Ignore $ignore
    ) {
        $this->_ignore = $ignore;
        $this->_options['packagedirectory'] = $package_directory;
        $this->_options['include'] = '*';
        $this->_options['ignore'] = array('*~', 'conf.php', 'CVS/*');
        $this->_options['packagefile'] = 'package.xml';
        $this->_options['addhiddenfiles'] = false;
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
    function _checkIgnore($file, $path, $return = 1)
    {
        $result = parent::_checkIgnore($file, $path, $return);
        if ($result == $return) {
            return $result;
        } else {
            return $this->_ignore->checkIgnore($file, $path, $return);
        }
    }
}