<?php
/**
 * Horde_Qc_Module:: interface represents a single quality control module.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Horde_Qc_Module:: interface represents a single quality control module.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */
class Horde_Qc_Module_PearPackageXml
extends Horde_Qc_Module
{
    public function getOptions()
    {
        return array(
            new Horde_Argv_Option(
                '-u',
                '--update-packagexml',
                array('action' => 'store_true')
            )
        );
    }

    public function run()
    {

PEAR::setErrorHandling(PEAR_ERROR_DIE);


        $package_file = $package_path . '/package.xml';

if (!file_exists($package_file)) {
    print sprintf("There is no package.xml at %s!\n", $package_path);
    exit(1);
}

$package = PEAR_PackageFileManager2::importOptions(
    $package_file,
    array(
        'packagedirectory' => $package_path,
        'filelistgenerator' => 'file',
        'clearcontents' => false,
        'clearchangelog' => false,
        'simpleoutput' => true,
        'include' => '*',
        'dir_roles' =>
        array(
            'lib'     => 'php',
            'doc'     => 'doc',
            'example' => 'doc',
            'script'  => 'script',
            'test'    => 'test',
        ),
    )
);

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

if (!empty($opts['update_packagexml'])) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}

    }
}
