<?php
/**
 * Components_Runner_PearPackageXml:: updates the package.xml of a Horde
 * component.
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
 * Components_Runner_PearPackageXml:: updates the package.xml of a Horde
 * component.
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
class Components_Runner_PearPackageXml
{
    /**
     * The configuration for the current job.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The package handler.
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * Constructor.
     *
     * @param Components_Config       $config  The configuration for the current job.
     * @param Components_Pear_Package $package Package handler.
     */
    public function __construct(
        Components_Config $config,
        Components_Pear_Package $package
    ) {
        $this->_config  = $config;
        $this->_package = $package;
    }

    public function run()
    {
        $arguments = $this->_config->getArguments();
        $package_file = $arguments[0] . '/package.xml';

        $pear = new PEAR();
        $pear->setErrorHandling(PEAR_ERROR_DIE);

        if (!isset($GLOBALS['_PEAR_Config_instance'])) {
            $GLOBALS['_PEAR_Config_instance'] = false;
        }

        $package = PEAR_PackageFileManager2::importOptions(
            $package_file,
            array(
                'packagedirectory' => $arguments[0],
                'filelistgenerator' => 'file',
                'clearcontents' => false,
                'clearchangelog' => false,
                'simpleoutput' => true,
                'ignore' => array('*~', 'conf.php', 'CVS/*'),
                'include' => '*',
                'dir_roles' =>
                array(
                    'doc'       => 'doc',
                    'example'   => 'doc',
                    'js'        => 'horde',
                    'lib'       => 'php',
                    'migration' => 'data',
                    'script'    => 'script',
                    'test'      => 'test',
                ),
            )
        );

        if ($package instanceOf PEAR_Error) {
            throw new Components_Exception($package->getMessage());
        }
        /**
         * @todo: Looks like this throws away any <replace /> tags we have in
         * the content list. Needs to be fixed.
         */
        $package->generateContents();

        /**
         * This is required to clear the <phprelease><filelist></filelist></phprelease>
         * section.
         */
        $package->setPackageType('php');

        $contents = $package->getContents();
        $files = $contents['dir']['file'];

        foreach ($files as $file) {
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
            case 'script':
                $filename = basename($file['attribs']['name']);
                if (substr($filename, strlen($filename) - 4)) {
                    $filename = substr($filename, 0, strlen($filename) - 4);
                }
                $package->addInstallAs(
                    $file['attribs']['name'], $filename
                );
                break;
            }
        }

        $options = $this->_config->getOptions();
        if (!empty($options['packagexml'])) {
            $package->debugPackageFile();
        }
        if (!empty($options['updatexml'])) {
            $package->writePackageFile();
        }

    }
}
