--TEST--
VFS_ISOWriter_RealOutputStrategy:: and drivers
--FILE--
<?php

require_once 'VFS.php';
require_once 'VFS/file.php';
require_once dirname(__FILE__) . '/../ISOWriter/RealOutputStrategy.php';

/**
 * This class is to make a file driver for VFS which isn't treated as a file
 * driver (strategies detect based on class name).
 */
class VFS_notfile extends VFS_file {
}

echo "Load... ok\n";
testDirectOutputStrategy();
testCopyOutputStrategy();

function testDirectOutputStrategy()
{
    echo "Testing direct output strategy... ";

    try {
        $vfs = VFS::factory('file', array('vfsroot' => '/tmp'));
        testOutputStrategy($vfs, 'vfs_isowriter_realoutputstrategy_direct');
    } catch (VFS_Exception $e) {
        echo "ERROR(1): ", $e->getMessage(), "\n";
    }
}

function testCopyOutputStrategy()
{
    echo "Testing copy output strategy... ";

    $vfs = new VFS_notfile(array('vfsroot' => '/tmp'));
    testOutputStrategy($vfs, 'vfs_isowriter_realoutputstrategy_copy');
}

function testOutputStrategy(&$vfs, $expectClass)
{
    $outputStrategy = &VFS_ISOWriter_RealOutputStrategy::factory($vfs, 'foo');
    if (is_a($outputStrategy, 'PEAR_Error')) {
        echo "ERROR(2): ", $outputStrategy->getMessage(), "\n";
        return;
    }

    if (get_class($outputStrategy) != $expectClass) {
        printf("ERROR(3): expected class '%s', got class '%s'.\n",
               $expectClass, get_class($outputStrategy));
        return;
    }

    $fn = $outputStrategy->getRealFilename();
    if (is_a($fn, 'PEAR_Error')) {
        echo "ERROR(4): ", $fn->getMessage(), "\n";
        return;
    }

    $fh = @fopen($fn, 'w');
    if (!is_resource($fh)) {
        printf("ERROR(5): could not open '%s' for writing.\n", $fn);
        return;
    }
    $data = md5(uniqid('foobar', true));
    fputs($fh, $data);
    fclose($fh);

    $res = $outputStrategy->finished();
    if (is_a($res, 'PEAR_Error')) {
        echo "ERROR(6): ", $res->getMessage(), "\n";
        return;
    }

    try {
        $res = $vfs->read('/', 'foo');
    } catch (VFS_Exception $e) {
        echo "ERROR(7): ", $e->getMessage(), "\n";
        return;
    }

    if ($res != $data) {
        printf("ERROR(8): file contents differ ('%s' vs. '%s').", $res,
               $data);
        return;
    }

    echo "ok\n";
}

--EXPECT--
Load... ok
Testing direct output strategy... ok
Testing copy output strategy... ok
