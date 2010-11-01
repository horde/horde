<?php
/**
 * Components_Pear_Package:: provides package handling mechanisms.
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
 * Components_Pear_Package:: provides package handling mechanisms.
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
class Components_Pear_Package
{
    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * The PEAR environment for the package.
     *
     * @param Components_Pear_InstallLocation
     */
    private $_environment;

    /**
     * The factory for PEAR class instances.
     *
     * @param Components_Pear_Factory
     */
    private $_factory;

    /**
     * The path to the package XML file.
     *
     * @param string
     */
    private $_package_xml_path;

    /**
     * The package representation.
     *
     * @param PEAR_PackageFile_v2
     */
    private $_package_file;

    /**
     * The writeable package representation.
     *
     * @param PEAR_PackageFileManager2
     */
    private $_package_rw_file;

    /**
     * Constructor.
     *
     * @param Component_Output $output The output handler.
     */
    public function __construct(Components_Output $output)
    {
        $this->_output = $output;
    }

    /**
     * Define the surrounding PEAR environment for the package.
     *
     * @param Components_Pear_InstallLocation
     *
     * @return NULL
     */
    public function setEnvironment(Components_Pear_InstallLocation $environment)
    {
        $this->_environment = $environment;
    }

    /**
     * Define the factory that creates our PEAR dependencies.
     *
     * @param Components_Pear_Factory
     *
     * @return NULL
     */
    public function setFactory(Components_Pear_Factory $factory)
    {
        $this->_factory = $factory;
    }

    /**
     * Return the PEAR environment for this package.
     *
     * @return Components_Pear_InstallLocation
     */
    public function getEnvironment()
    {
        if ($this->_environment === null) {
            throw new Component_Exception('You need to set the environment first!');
        }
        return $this->_environment;
    }

    /**
     * Define the package to work on.
     *
     * @param string $package_xml_path Path to the package.xml file.
     *
     * @return NULL
     */
    public function setPackageXml($package_xml_path)
    {
        $this->_package_xml_path = $package_xml_path;
    }

    /**
     * Return the PEAR Package representation.
     *
     * @return PEAR_PackageFile
     */
    private function _getPackageFile()
    {
        $this->_checkSetup();
        if ($this->_package_file === null) {
            $this->_package_file = $this->_factory->getPackageFile(
                $this->_package_xml_path,
                $this->getEnvironment()
            );
        }
        return $this->_package_file;
    }

    /**
     * Return a writeable PEAR Package representation.
     *
     * @return PEAR_PackageFileManager2
     */
    private function _getPackageRwFile()
    {
        $this->_checkSetup();
        if ($this->_package_rw_file === null) {
            $this->_package_rw_file = $this->_factory->getPackageRwFile(
                $this->_package_xml_path,
                $this->getEnvironment()
            );
        }
        return $this->_package_rw_file;
    }

    /**
     * Validate that the required parameters for providing the package definition are set.
     *
     * @return NULL
     *
     * @throws Components_Exception In case some settings are missing.
     */
    private function _checkSetup()
    {
        if ($this->_environment === null
            || $this->_package_xml_path === null
            || $this->_factory === null) {
            throw new Components_Exception('You need to set the factory, the environment and the path to the package file first!');
        }
    }

    /**
     * Return the name for this package.
     *
     * @return string The package name.
     */
    public function getName()
    {
        return $this->_getPackageFile()->getName();
    }

    /**
     * Return the description for this package.
     *
     * @return string The package description.
     */
    public function getDescription()
    {
        return $this->_getPackageFile()->getDescription();
    }

    /**
     * Return the version for this package.
     *
     * @return string The package version.
     */
    public function getVersion()
    {
        return $this->_getPackageFile()->getVersion();
    }

    /**
     * Return the license for this package.
     *
     * @return string The package license.
     */
    public function getLicense()
    {
        return $this->_getPackageFile()->getLicense();
    }

    /**
     * Return the summary for this package.
     *
     * @return string The package summary.
     */
    public function getSummary()
    {
        return $this->_getPackageFile()->getSummary();
    }

    /**
     * Update the content listing of the provided package.
     *
     * @param PEAR_PackageFileManager2 $package The package to update.
     *
     * @return NULL
     */
    private function _updateContents(PEAR_PackageFileManager2 $package)
    {
        $contents = $package->getContents();
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

        $package->generateContents();

        $updated = $package->getContents();
        $updated = $updated['dir']['file'];
        foreach ($updated as $file) {
            if (!isset($file['attribs'])) {
                continue;
            }
            if (isset($taskfiles[$file['attribs']['name']])) {
                foreach ($taskfiles[$file['attribs']['name']] as $tag => $raw) {
                    $taskname = $package->getTask($tag) . '_rw';
                    if (!class_exists($taskname)) {
                        throw new Components_Exception(
                            sprintf('Read/write task %s is missing!', $taskname)
                        );
                    }
                    $logger = new stdClass;
                    $task = new $taskname(
                        $package,
                        $this->getEnvironment()->getPearConfig(),
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
                    $package->addTaskToFile($file['attribs']['name'], $task);
                }
            }
        }
    }

    /**
     * Return an updated package description.
     *
     * @return PEAR_PackageFileManager2 The updated package.
     */
    private function _getUpdatedPackageFile()
    {
        $package = $this->_getPackageRwFile();

        $this->_updateContents($package);

        /**
         * This is required to clear the <phprelease><filelist></filelist></phprelease>
         * section.
         */
        $package->setPackageType('php');

        $contents = $package->getContents();
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
                $package->addInstallAs(
                    $file['attribs']['name'], $components[1]
                );
            break;
            case 'js':
            case 'horde':
                $horde_role = true;
            case 'locale':
                $package->addInstallAs(
                    $file['attribs']['name'], $file['attribs']['name']
                );
            break;
            case 'migration':
                $components = explode('/', $components[1]);
                array_splice($components, count($components) - 1, 0, 'migration');
                $package->addInstallAs(
                    $file['attribs']['name'], implode('/', $components)
                );
                break;
            case 'bin':
            case 'script':
                $filename = basename($file['attribs']['name']);
                if (substr($filename, strlen($filename) - 4) == '.php') {
                    $filename = substr($filename, 0, strlen($filename) - 4);
                }
                $package->addInstallAs(
                    $file['attribs']['name'], $filename
                );
                break;
            }
        }

        if ($horde_role) {
            $roles = $package->getUsesrole();
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
                $package->addUsesrole(
                    'horde', 'Role', 'pear.horde.org'
                );
            }
        }

        return $package;
    }

    /**
     * Output the updated package.xml file.
     *
     * @return NULL
     */
    public function printUpdatedPackageFile()
    {
        $this->_getUpdatedPackageFile()->debugPackageFile();
    }    

    /**
     * Write the updated package.xml file to disk.
     *
     * @return NULL
     */
    public function writeUpdatedPackageFile()
    {
        $this->_getUpdatedPackageFile()->writePackageFile();
        $this->_output->ok('Successfully updated ' . $this->_package_xml_path);
    }    

    /**
     * Return all channels required for this package and its dependencies.
     *
     * @return array The list of channels.
     */
    public function listAllRequiredChannels()
    {
        $dependencies = array();
        foreach ($this->_getPackageFile()->getDeps() as $dependency) {
            if (isset($dependency['channel'])) {
                $dependencies[] = $dependency['channel'];
            }
        }
        $dependencies[] = $this->_getPackageFile()->getChannel();
        return array_unique($dependencies);
    }    

    /**
     * Return all channels required for this package and its dependencies.
     *
     * @return array The list of channels.
     */
    public function listAllExternalDependencies()
    {
        $dependencies = array();
        foreach ($this->_getPackageFile()->getDeps() as $dependency) {
            if (isset($dependency['channel']) && $dependency['channel'] != 'pear.horde.org') {
                $dependencies[] = $dependency;
            }
        }
        return $dependencies;
    }    

    /**
     * Return all channels required for this package and its dependencies.
     *
     * @return array The list of channels.
     */
    public function listAllHordeDependencies()
    {
        $dependencies = array();
        foreach ($this->_getPackageFile()->getDeps() as $dependency) {
            if (isset($dependency['channel']) && $dependency['channel'] == 'pear.horde.org') {
                $dependencies[] = $dependency;
            }
        }
        return $dependencies;
    }    

    /**
     * Generate a snapshot of the package using the provided version number.
     *
     * @param string $version     The snapshot version.
     * @param string $archive_dir The path where the snapshot should be placed.
     *
     * @return string The path to the snapshot.
     */
    public function generateSnapshot($version, $archive_dir)
    {
        $pkg = $this->_getPackageFile();
        $pkg->_packageInfo['version']['release'] = $version;
        $pkg->setDate(date('Y-m-d'));
        $pkg->setTime(date('H:i:s'));
        ob_start();
        $old_dir = getcwd();
        chdir($archive_dir);
        $result = Components_Exception_Pear::catchError(
            $pkg->getDefaultGenerator()
            ->toTgz(new PEAR_Common())
        );
        chdir($old_dir);
        $this->_output->pear(ob_get_clean());
        $this->_output->ok('Generated snapshot ' . $result);
        return $result;
    }

}
