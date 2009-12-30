#!@php_bin@
<?php
/**
 * A small script that takes lines of a commit message like:
 * <pre>
 *   2.485     +26 -5     imp/compose.php
 *   1.503     +2 -0      imp/docs/CHANGES
 *   2.159     +25 -12    imp/templates/compose/compose.inc
 *   2.55      +28 -3     imp/templates/compose/javascript.inc
 * </pre>
 * from the standard input and merges these commits into the appropriate files
 * of the current directory. Mainly for merging changes in HEAD to the RELENG
 * tree. This script should be run from Horde's root.
 *
 * @category Horde
 * @package  devtools
 */

// Location of the cvs binary
$CVS = 'cvs';

@set_time_limit(0);
ob_implicit_flush(true);
ini_set('track_errors', true);
ini_set('implicit_flush', true);
ini_set('html_errors', false);
ini_set('magic_quotes_runtime', false);

$reverse = $join = $commit = $compress = false;
$target = '.';
while ($arg = array_shift($argv)) {
    switch ($arg) {
    case '-R':
        $reverse = true;
        break;
    case '-j':
        $join = true;
        break;
    case '-c':
        $commit = true;
        break;
    case '-z':
        $compress = true;
        break;
    case '-t':
        $target = array_shift($argv);
        break;
    }
}
$target .= '/';

$lines = array();
while (!feof(STDIN)) {
    $lines[] = fgets(STDIN);
}
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) {
        continue;
    }
    $tok = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
    $file = str_replace('Attic/', '', $tok[3]);
    if (isset($tok[4]) && $tok[4] == '(dead)') {
        $cmd = $CVS . ' remove -f ' . $file;
        $new_version = $tok[0];
    } elseif (isset($tok[4]) && $tok[4] == '(new)') {
        $cmd = '';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            $cmd = "$CVS -f co $dir; ";
        }
        $cmd .= $CVS . ' up -j ' . $tok[0] . ' ' . $file;
        $new_version = $tok[0];
    } else {
        if (count($tok) != 4) {
            print "Unknown line format:\n" . $line . "\n";
            continue;
        }
        $new_version = explode('.', $tok[0]);
        $old_version = $new_version;
        $old_version[count($old_version) - 1]--;
        if ($old_version[count($old_version) - 1] == 0) {
            unset($old_version[count($old_version) - 1]);
            unset($old_version[count($old_version) - 1]);
        }
        $new_version = implode('.', $new_version);
        $old_version = implode('.', $old_version);
        if ($reverse) {
            $tmp = $new_version;
            $new_version = $old_version;
            $old_version = $tmp;
        }
        if ($join) {
            $cmd = sprintf($CVS . ' up -j %s -j %s -kk %s',
                           $old_version,
                           $new_version,
                           $file);
        } else {
            $cmd = sprintf($CVS . ' diff -N -r %s -r %s -kk %s | patch %s',
                           $old_version,
                           $new_version,
                           str_replace('horde/', '', $file),
                           $target . $file);
        }
    }
    print $cmd . "\n";
    system($cmd . ' 2>&1', $exit);
    print "\n";

    if ($exit !== 0) {
        continue;
    }

    // Compress JS files if necessary
    if ($compress && $old_version &&
        preg_match('/^([^\/]+)\/(.+)\/(.+)$/', $file, $matches) &&
        $matches[2] == 'js/src') {
        $currdir = getcwd();
        chdir(implode('/', array($matches[1], $matches[2])));
        passthru('php ' . $currdir . '/framework/devtools/horde-js-compress.php --file=' . $matches[3]);
        chdir($currdir);
        if ($commit) {
            print "Commit doesn't work for compressed JS files - you need to commit manually.\n";
        }
    }

    if ($commit) {
        $cmd = sprintf($CVS . ' ci -m "%s: %s" %s',
                       $reverse ? 'Revert' : (substr_count($new_version, '.') > 1 ? 'MFB' : 'MFH'),
                       $reverse ? $old_version : $new_version,
                       $file);
        print $cmd . "\n";
        system($cmd . ' 2>&1');
    }
}
