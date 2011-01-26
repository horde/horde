<?php
/**
 * Components_Pear_Package_Filelist_Default:: is the default file list handler.
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
 * Components_Pear_Package_Filelist_Default:: is the default file list handler.
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
class Components_Pear_Package_Filelist_Default
{
    /**
     * The package to operate on.
     *
     * @var PEAR_PackageFile_v2_rw
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param PEAR_PackageFile_v2_rw $package The package to operate on.
     */
    public function __construct(PEAR_PackageFile_v2_rw $package)
    {
        $this->_package = $package;
    }

    /**
     * Update the file list.
     *
     * @return NULL
     */
    public function update()
    {
        /**
         * This is required to clear the <phprelease><filelist></filelist></phprelease>
         * section.
         */
        $this->_package->setPackageType('php');

        $contents = $this->_package->getContents();
        $files = $contents['dir']['file'];
        $horde_role = false;

        foreach ($files as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            if (strpos($file['attribs']['name'], '/') !== false) {
                $components = explode('/', $file['attribs']['name'], 2);
            } else {
                $components = array('', $file['attribs']['name']);
            }
            $role = isset($file['attribs']['role']) ? $file['attribs']['role'] : '';
            switch ($role) {
            case 'horde':
                $horde_role = true;
                if (in_array(
                        $this->_package->getName(),
                        array('content', 'imp', 'ingo', 'kronolith', 'mnemo', 'nag', 'turba')
                    )) {
                    $prefix = $this->_package->getName() . '/';
                } else {
                    $prefix = '';
                }
                $this->_package->addInstallAs(
                    $file['attribs']['name'], $prefix . $file['attribs']['name']
                );
                break;
            case 'doc':
            case 'test':
                $this->_package->addInstallAs(
                    $file['attribs']['name'], $components[1]
                );
                break;
            case 'script':
                $filename = basename($file['attribs']['name']);
                if (substr($filename, strlen($filename) - 4) == '.php') {
                    $filename = substr($filename, 0, strlen($filename) - 4);
                }
                $this->_package->addInstallAs(
                    $file['attribs']['name'], $filename
                );
                break;
            case 'php':
            case 'data':
            default:
                switch ($components[0]) {
                case 'lib':
                case 'data':
                    $this->_package->addInstallAs(
                        $file['attribs']['name'], $components[1]
                    );
                break;
                case 'locale':
                    $this->_package->addInstallAs(
                        $file['attribs']['name'], $file['attribs']['name']
                    );
                    break;
                case 'migration':
                    $components = explode('/', $components[1]);
                    array_splice($components, count($components) - 1, 0, 'migration');
                    $this->_package->addInstallAs(
                        $file['attribs']['name'], implode('/', $components)
                    );
                    break;
                default:
                    $this->_package->addInstallAs(
                        $file['attribs']['name'], $file['attribs']['name']
                    );
                    break;
                }
            }
        }

        if ($horde_role) {
            $roles = $this->_package->getUsesrole();
            if (!empty($roles)) {
                if (isset($roles['role'])) {
                    $roles = array($roles);
                }
                foreach ($roles as $role) {
                    if (isset($role['role']) && $role['role'] == 'horde') {
                        $horde_role = false;
                        break;
                    }
                }
            }
            if ($horde_role) {
                $this->_package->addUsesrole(
                    'horde', 'Role', 'pear.horde.org'
                );
            }
        }
    }
}