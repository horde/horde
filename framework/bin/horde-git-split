#!/usr/bin/env php
<?php
/**
 * Usage: horde-git-split [tag-prefix] [subdirectory]
 * Example: horde-git-split horde_imap_client framework/Imap_Client
 */

if ($argc != 3) {
    exit;
}

$base = dirname(realpath(dirname(__FILE__) . '/../'));
$tmp = sys_get_temp_dir() . '/' . mt_rand();

mkdir($tmp);
chdir($tmp);
system('git clone --bare ' . escapeshellarg($base) . ' tmp');
chdir($tmp . '/tmp');
system('git remote rm origin');

$delete = array();
foreach (array_filter(explode("\n", shell_exec('git tag -l'))) as $val) {
    if (strpos($val, $argv[1] . '-') === false) {
        $delete[] = escapeshellarg($val);
    }
}
if (count($delete)) {
    system('git tag -d ' . implode(' ', $delete));
}

system("git filter-branch --prune-empty --subdirectory-filter " . $argv[2] . " --tag-name-filter cat -- --all");
system('git update-ref -d refs/original/refs/heads/master');
system('git reflog expire --expire=now --all');
system('git gc --aggressive --prune=now');
chdir($tmp);
system('git clone --bare file://' . $tmp . '/tmp split');
chdir($tmp . '/split');
system('git gc --aggressive --prune=now');

print "Split repo in: " . $tmp . "/split\n";
