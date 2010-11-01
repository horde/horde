<?php
/**
 * Components_Pear_Package_Contents:: handles the PEAR package content.
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
 * Components_Pear_Package_Contents:: handles the PEAR package content.
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
class Components_Pear_Package_Contents
{
    /**
     * The package to work on.
     *
     * @var PEAR_PackageFileManager2
     */
    private $_package;

    /**
     * Set the package that should be handled.
     *
     * @param PEAR_PackageFileManager2 $package The package to work on.
     *
     * @return NULL
     */
    public function setPackage(PEAR_PackageFileManager2 $package)
    {
        $this->_package = $package;
    }

    /**
     * Set the package that should be handled.
     *
     * @param PEAR_PackageFileManager2 $package The package to work on.
     *
     * @return NULL
     */
    public function getPackage()
    {
        if (empty($this->_package)) {
            throw new Components_Exception('Set the package first!');
        }
        return $this->_package;
    }

    /**
     * Update the content listing of the provided package.
     *
     * @return NULL
     */
    private function _updateContents()
    {
        $contents = $this->getPackage()->getContents();
        $contents = $contents['dir']['file'];
        $taskfiles = array();
        foreach ($contents as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            $atts = $file['attribs'];
            unset($file['attribs']);
            if (count($file)) {
                $taskfiles[$atts['name']] = $file;
            }
        }

        $this->getPackage()->generateContents();

        $updated = $this->getPackage()->getContents();
        $updated = $updated['dir']['file'];
        foreach ($updated as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            if (isset($taskfiles[$file['attribs']['name']])) {
                foreach ($taskfiles[$file['attribs']['name']] as $tag => $raw) {
                    $taskname = $this->getPackage()->getTask($tag) . '_rw';
                    if (!class_exists($taskname)) {
                        throw new Components_Exception(
                            sprintf('Read/write task %s is missing!', $taskname)
                        );
                    }
                    $logger = new stdClass;
                    $task = new $taskname(
                        $this->getPackage(),
                        $this->getPackage()->_config,
                        $logger,
                        ''
                    );
                    switch ($taskname) {
                    case 'PEAR_Task_Replace_rw':
                        $task->setInfo(
                            $raw['attribs']['from'],
                            $raw['attribs']['to'],
                            $raw['attribs']['type']
                        );
                        break;
                    default:
                        throw new Components_Exception(
                            sprintf('Unsupported task type %s!', $tag)
                        );
                    }
                    $task->init(
                        $raw,
                        $file['attribs']
                    );
                    $this->getPackage()->addTaskToFile($file['attribs']['name'], $task);
                }
            }
        }
    }

    /**
     * Return an updated package description.
     *
     * @return PEAR_PackageFileManager2 The updated package.
     */
    public function update()
    {
        $this->_updateContents();

        /**
         * This is required to clear the <phprelease><filelist></filelist></phprelease>
         * section.
         */
        $this->getPackage()->setPackageType('php');

        $contents = $this->getPackage()->getContents();
        $files = $contents['dir']['file'];
        $horde_role = false;

        foreach ($files as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            $components = explode('/', $file['attribs']['name'], 2);
            switch ($components[0]) {
            case 'doc':
            case 'example':
            case 'lib':
            case 'test':
            case 'data':
                $this->getPackage()->addInstallAs(
                    $file['attribs']['name'], $components[1]
                );
            break;
            case 'js':
            case 'horde':
                $horde_role = true;
            case 'locale':
                $this->getPackage()->addInstallAs(
                    $file['attribs']['name'], $file['attribs']['name']
                );
            break;
            case 'migration':
                $components = explode('/', $components[1]);
                array_splice($components, count($components) - 1, 0, 'migration');
                $this->getPackage()->addInstallAs(
                    $file['attribs']['name'], implode('/', $components)
                );
                break;
            case 'bin':
            case 'script':
                $filename = basename($file['attribs']['name']);
                if (substr($filename, strlen($filename) - 4) == '.php') {
                    $filename = substr($filename, 0, strlen($filename) - 4);
                }
                $this->getPackage()->addInstallAs(
                    $file['attribs']['name'], $filename
                );
                break;
            }
        }

        if ($horde_role) {
            $roles = $this->getPackage()->getUsesrole();
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
                $this->getPackage()->addUsesrole(
                    'horde', 'Role', 'pear.horde.org'
                );
            }
        }
    }
}