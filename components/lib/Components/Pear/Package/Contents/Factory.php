<?php
/**
 * Components_Pear_Package_Contents_Factory:: handles the different contents
 * list generators.
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
 * Components_Pear_Package_Contents_Factory:: handles the different content list
 * generators.
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
class Components_Pear_Package_Contents_Factory
{
    /**
     * Create the contents handler.
     *
     * @param PEAR_PackageFile_v2_rw $package The package.
     *
     * @return Components_Pear_Package_Contents_List The content handler.
     */
    public function create(PEAR_PackageFile_v2_rw $package)
    {
        $root = new Components_Helper_Root(
            $package->_options['packagedirectory']
        );
        $package->_options['roles'] = $this->_getRoles($root->getBase());
        $package->_options['dir_roles'] = $this->_getMapping($root->getBase());
        return new Components_Pear_Package_Contents_List(
            $package->_options['packagedirectory'],
            new Components_Pear_Package_Contents_Ignore(
                $root->getGitIgnore(),
                $root->getRoot()
            )
        );
    }

    /**
     * Return the directory <-> role mapping for the specific package.
     *
     * @param string $path The package path.
     *
     * @return array The mapping.
     */
    private function _getMapping($path)
    {
        switch ($path) {
        case 'content/':
        case 'horde/':
        case 'imp/':
        case 'ingo/':
        case 'kronolith/':
        case 'mnemo/':
        case 'nag/':
        case 'turba/':
            return array(
                'bin'       => 'script',
                'config'    => 'horde',
                'script'    => 'script',
                'docs'      => 'doc',
                'js'        => 'horde',
                'locale'    => 'horde',
                'scripts'   => 'data',
                'test'      => 'test',
                'templates' => 'horde',
                'themes'    => 'horde',
                'util'      => 'horde',
            );
        default:
            return array(
                'bin'       => 'script',
                'script'    => 'script',
                'doc'       => 'doc',
                'example'   => 'doc',
                'js'        => 'horde',
                'horde'     => 'horde',
                'lib'       => 'php',
                'migration' => 'data',
                'scripts'   => 'data',
                'test'      => 'test',
            );
        }
    }

    /**
     * Return the default role mapping for the specific package.
     *
     * @param string $path The package path.
     *
     * @return array The mapping.
     */
    private function _getRoles($path)
    {
        switch ($path) {
        case 'content/':
        case 'horde/':
        case 'imp/':
        case 'ingo/':
        case 'kronolith/':
        case 'mnemo/':
        case 'nag/':
        case 'turba/':
            return array(
                'h'    => 'src',
                'c'    => 'src',
                'cpp'  => 'src',
                'in'   => 'src',
                'm4'   => 'src',
                'w32'  => 'src',
                'dll'  => 'ext',
                'php'  => 'horde',
                'html' => 'doc',
                '*'    => 'data',
            );
        default:
            return array(
                'h'    => 'src',
                'c'    => 'src',
                'cpp'  => 'src',
                'in'   => 'src',
                'm4'   => 'src',
                'w32'  => 'src',
                'dll'  => 'ext',
                'php'  => 'php',
                'html' => 'doc',
                '*'    => 'data',
            );
        }
    }
}