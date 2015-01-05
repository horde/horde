<?php
/**
 * Create MIME mapping file from data sources.
 *
 * Copyright 2001-2015 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Anil Madhavapeddy <avsm@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2001-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/* Files containing MIME extensions (Apache format).
 * https://github.com/apache/httpd/blob/trunk/docs/conf/mime.types */
$files = array(
    'mime.types',
    'mime.types.horde'
);

/* Files contating MIME extensions (freedesktop.org format).
 * http://www.freedesktop.org/wiki/Specifications/shared-mime-info-spec */
$od_files = array(
    'mime.globs'
);

$exts = array();
$maxlength = strlen('__MAXPERIOD__');
$maxperiod = 0;

/* Map the mime extensions file(s) into the $exts hash. */
foreach ($files as $val) {
    /* Read file and remove trailing whitespace. */
    $data = array_filter(array_map('rtrim', file($val)));

    foreach ($data as $line) {
        /* Skip comments. */
        if ($line[0] === '#') {
            continue;
        }

        /* These are tab-delimited files. Skip the entry if there is no
         * extension information. */
        $fields = preg_split("/\s+/", $line, 2);
        if (!empty($fields[1])) {
            foreach (preg_split("/\s+/", $fields[1]) as $val2) {
                $exts[$val2] = $fields[0];
                $maxlength = max(strlen($val2), $maxlength);
            }
        }
    }
}

foreach ($od_files as $val) {
    /* Read file and remove trailing whitespace. */
    $data = array_filter(array_map('rtrim', file($val)));

    foreach ($data as $line) {
        /* Skip comments. */
        if ($line[0] === '#') {
            continue;
        }

        /* These are ':' delimited files. Skip the entry if this is not
           an extension matching glob. */
        $fields = explode(':', $line, 2);
        $pos = strpos($fields[1], '*.');
        if ($pos !== false) {
            $val2 = substr($fields[1], $pos + 2);
            if ((strpos($val2, '*') !== false) ||
                (strpos($val2, '[') !== false) ||
                isset($exts[$val2])) {
                continue;
            }
            $maxperiod = max(substr_count($val2, '.'), $maxperiod);
            $maxlength = max(strlen($val2), $maxlength);
            $exts[$val2] = $fields[0];
        }
    }
}

/* Assemble/sort the extensions into an output array. */
$output = array(
    sprintf(
        "'__MAXPERIOD__'%s => '%u'",
        str_repeat(' ', $maxlength - strlen('__MAXPERIOD__')),
        $maxperiod
    )
);

ksort($exts);

/* Special case: move .jpg to the first image/jpeg entry. */
$first_jpeg = array_search('image/jpeg', $exts);
$keys = array_keys($exts);
$index1 = array_search($first_jpeg, $keys);
$index2 = array_search('jpg', $keys);
$keys[$index1] = 'jpg';
$keys[$index2] = $first_jpeg;
$exts = array_combine($keys, array_values($exts));

foreach ($exts as $key => $val) {
    $output[] = sprintf(
        "'%s'%s => '%s'",
        $key,
        str_repeat(' ', $maxlength - strlen($key)),
        $val
    );
}

/* Generate the PHP output file. */
$generated = sprintf(
    '%s by %s on %s',
    strftime('%D %T'),
    $_SERVER['USER'],
    $_SERVER['HOST']
);
$map = implode(",\n    ", $output);

print <<<HEADER
<?php
/**
 * This file contains a mapping of common file extensions to MIME types.
 * It has been automatically generated.
 * Any changes made directly to this file may/will be lost in the future.
 *
 * Any unknown file extensions will automatically be mapped to
 * 'x-extension/<ext>' where <ext> is the unknown file extension.
 *
 * Generated: $generated
 *
 * @category Horde
 * @package  Mime
 */
\$mime_extension_map = array(
    $map
);
HEADER;
