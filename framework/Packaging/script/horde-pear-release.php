#!/usr/bin/env php
<?php

require_once 'Horde/Core/Autoloader.php';

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$options = array(
    new Horde_Argv_Option(
        '-u', '--update-packagexml', array('action' => 'store_true')
    ),
);
$parser = new Horde_Argv_Parser(
    array(
        'optionList' => $options,
        'usage' => '%prog ' . _("[options] PACKAGE_PATH")
    )
);
list($opts, $args) = $parser->parseArgs();

if (empty($args[0])) {
    echo "Please specify the path to the package you want to release!\n\n";
    $parser->printUsage(STDERR);
    exit(1);
}

$package_path = $args[0];
if (!is_dir($package_path)) {
    printf("%s specifies no directory!\n", $package_path);
    exit(1);
}

$package_file = $package_path . '/package.xml';

if (!file_exists($package_file)) {
    printf("There is no package.xml at %s!\n", $package_path);
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

if (!empty($opts['update_packagexml'])) {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
}
