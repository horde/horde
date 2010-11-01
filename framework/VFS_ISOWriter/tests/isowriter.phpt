--TEST--
VFS_ISOWriter:: and drivers
--FILE--
<?php

require_once 'VFS.php';
require_once dirname(__FILE__) . '/../ISOWriter.php';

echo "Load... ok\n";

echo "Creating VFS... ";
try {
    $vfs = VFS::factory('file', array('vfsroot' => '/tmp'));
} catch (VFS_Exception $e) {
    printf("ERROR(1): %s\n", $e->getMessage);
    exit;
}
echo "ok\n";

echo "Populating VFS... ";
$FILES = array('a' => md5(uniqid('a', true)),
               'c/d' => md5(uniqid('c/d', true)),
               'e/f' => md5(uniqid('e/f', true)));

foreach ($FILES as $fname => $data) {
    preg_match('!^(.*)/([^/]*)$!', 'root/' . $fname, $matches);
    $path = $matches[1];
    $file = $matches[2];
    try {
        $vfs->writeData($path, $file, $data, true);
    } catch (VFS_Exception $e) {
        printf("ERROR(1): %s\n", $e->getMessage());
        exit;
    }
}
echo "ok\n";

echo "Creating ISOWriter... ";
$iso = &VFS_ISOWriter::factory($vfs, $vfs, array('sourceRoot' => 'root',
                                                 'targetFile' => 'test.iso'));
if (is_a($iso, 'PEAR_Error')) {
    printf("ERROR(1): %s\n", $iso->getMessage());
    exit;
}
echo "ok\n";

echo "Creating ISO Image... ";
$res = $iso->process();
if (is_a($res, 'PEAR_Error')) {
    printf("ERROR(1): %s\n", $res->getMessage());
    exit;
}
if (!file_exists('/tmp/test.iso')) {
    printf("ERROR(2): /tmp/test.iso does not exist after creating image.\n");
    exit;
}
echo "ok\n";

echo "Checking ISO Image (if possible)... ";
system("/sbin/modprobe loop >/dev/null 2>&1");
system("/sbin/losetup /dev/loop3 /tmp/test.iso >/dev/null 2>&1", $ec);
if ($ec == 0) {
    if (!@mkdir("/tmp/iso-mount", 0755)) {
        printf("ERROR(1): mkdir /tmp/iso-mount failed.\n");
        exit;
    }
    system("/bin/mount -t iso9660 /dev/loop3 /tmp/iso-mount >/dev/null", $ec);
    if ($ec != 0) {
        @rmdir("/tmp/iso-mount");
        printf("ERROR(2): mount of ISO image failed.\n");
        exit;
    }
    foreach ($FILES as $fname => $data) {
        $path = '/tmp/iso-mount/' . $fname;
        if (!file_exists($path)) {
            @rmdir("/tmp/iso-mount");
            printf("ERROR(3): %s: does not exist.\n", $path);
            exit;
        }
        $fh = @fopen($path, 'r');
        if (!is_resource($fh)) {
            @rmdir("/tmp/iso-mount");
            printf("ERROR(4): %s: could not open.\n", $path);
            exit;
        }
        $readData = fread($fh, filesize($path));
        fclose($fh);
        if ($data != $readData) {
            @rmdir("/tmp/iso-mount");
            printf("ERROR(5): %s: data does not match\n", $path);
            exit;
        }
    }
    system("/bin/umount /dev/loop3 >/dev/null 2>&1", $ec);
    system("/sbin/losetup -d /dev/loop3 >/dev/null", $ec);
    @rmdir("/tmp/iso-mount");
}
echo "ok\n";

--EXPECT--
Load... ok
Creating VFS... ok
Populating VFS... ok
Creating ISOWriter... ok
Creating ISO Image... ok
Checking ISO Image (if possible)... ok
