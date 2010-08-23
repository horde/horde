<?php
/**
 * Horde_Element_Module_PearPackageXml:: can update the package.xml of
 * a Horde element.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * Horde_Element_Module_PearPackageXml:: can update the package.xml of
 * a Horde element.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
class Horde_Element_Module_PearPackageXml
implements Horde_Element_Module
{
    public function getOptionGroupTitle()
    {
        return 'Pear Package Xml';
    }

    public function getOptionGroupDescription()
    {
        return 'This module allows manipulation of the package.xml.';
    }

    public function getOptionGroupOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-u',
                '--updatexml',
                array(
                    'action' => 'store_true',
                    'help'   => 'update the package.xml for the package'
                )
            ),
            new Horde_Argv_Option(
                '-p',
                '--packagexml',
                array(
                    'action' => 'store_true',
                    'help'   => 'display an up-to-date package.xml for the package'
                )
            )

        );
    }

    public function handle(Horde_Element_Config $config)
    {
        $options = $config->getOptions();
        if (!empty($options['packagexml']) ||
            !empty($options['updatexml'])) {
            $this->run($config);
        }
    }

    public function run(Horde_Element_Config $config)
    {
        $arguments = $config->getArguments();
        $package_file = $arguments[0] . '/package.xml';

        $pear = new PEAR();
        $pear->setErrorHandling(PEAR_ERROR_DIE);

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
                    'lib'       => 'php',
                    'doc'       => 'doc',
                    'example'   => 'doc',
                    'script'    => 'script',
                    'test'      => 'test',
                    'migration' => 'data',
                ),
            )
        );

        if ($package instanceOf PEAR_Error) {
            throw new Horde_Element_Exception($package->getMessage());
        }
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
                $package->addInstallAs(
                    $file['attribs']['name'], $components[1]
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

        $options = $config->getOptions();
        if (!empty($options['packagexml'])) {
            $package->debugPackageFile();
        }
        if (!empty($options['updatexml'])) {
            $package->writePackageFile();
        }

    }
}
