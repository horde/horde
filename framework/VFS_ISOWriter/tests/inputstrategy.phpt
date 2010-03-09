--TEST--
VFS_ISOWriter_RealInputStrategy:: and drivers
--FILE--
<?php

require_once 'VFS.php';
require_once 'VFS/file.php';
require_once dirname(__FILE__) . '/../ISOWriter/RealInputStrategy.php';

/**
 * This class is to make a file driver for VFS which isn't treated as a file
 * driver (strategies detect based on class name).
 */
class VFS_notfile extends VFS_file {
}

echo "Load... ok\n";

testDirectInputStrategy();
testCopyInputStrategy();

function testDirectInputStrategy()
{
    echo "Testing direct input strategy... ";

    $vfs = VFS::factory('file', array('vfsroot' => '/tmp'));
    testInputStrategy($vfs, 'vfs_isowriter_realinputstrategy_direct');
}

function testCopyInputStrategy()
{
    echo "Testing copy input strategy... ";

    $vfs = &new VFS_notfile(array('vfsroot' => '/tmp'));
    testInputStrategy($vfs, 'vfs_isowriter_realinputstrategy_copy');
}

function testInputStrategy(&$vfs, $expectClass)
{
    /* Contents for generated files. */
    $contents = array('a' => md5(uniqid('a', true)),
                      'd/b' => md5(uniqid('b', true)),
                      'd/e/c' => md5(uniqid('c', true)));

    foreach ($contents as $name => $data) {
        if (preg_match('!^(.*)/([^/]*)$!', $name, $matches)) {
            $dir = $matches[1];
            $file = $matches[2];
        } else {
            $dir = '';
            $file = $name;
        }

        try {
            $vfs->writeData('root/' . $dir, $file, $data, true);
        } catch (VFS_Exception $e) {
            printf("ERROR(1): %s: %s\n", $name, $e->getMessage());
            return;
        }
    }

    $inputStrategy = &VFS_ISOWriter_RealInputStrategy::factory($vfs, 'root');
    if (is_a($inputStrategy, 'PEAR_Error')) {
        printf("ERROR(2): %s\n", $inputStrategy->getMessage());
        return;
    }

    if ($expectClass != get_class($inputStrategy)) {
        printf("ERROR(3): expected class '%s', got '%s'.\n", $expectClass,
               get_class($inputStrategy));
        return;
    }

    $realPath = $inputStrategy->getRealPath();
    if (is_a($realPath, 'PEAR_Error')) {
        printf("ERROR(4): %s\n", $realPath->getMessage());
        return;
    }

    foreach ($contents as $name => $data) {
        $path = sprintf('%s/%s', $realPath, $name);
        if (!file_exists($path)) {
            printf("ERROR(5): file '%s' does not exist.\n", $path);
            return;
        }

        $fh = @fopen($path, 'r');
        if (!is_resource($fh)) {
            printf("ERROR(6): could not open '%s' for reading.\n", $path);
            return;
        }
        $fileData = fread($fh, filesize($path));
        fclose($fh);
        if ($fileData != $data) {
            printf("ERROR(7): %s: contents should be '%s' but got '%s'.\n",
                   $path, $data, $fileData);
            return;
        }
    }

    $res = $inputStrategy->finished();
    if (is_a($res, 'PEAR_Error')) {
        printf("ERROR(8): %s\n", $res->getMessage());
        return;
    }

    foreach ($contents as $name => $data) {
        if (preg_match('!^(.*)/([^/]*)$!', $name, $matches)) {
            $dir = $matches[1];
            $file = $matches[2];
        } else {
            $dir = '';
            $file = $name;
        }
        $vfs->deleteFile('root/' . $dir, $file);
    }

    $vfs->deleteFolder('root', 'd', true);
    echo "ok\n";
}

--EXPECT--
Load... ok
Testing direct input strategy... ok
Testing copy input strategy... ok
