<?php
/**
 * Displays an image and allows modifications if required.
 *
 * URL parameters:
 *   - a: perform some action on the image, such as scaling.
 *   - c: which app's config to use for VFS, defaults to Horde.
 *   - f: the filename.
 *   - n: the name to set to the filename or default to same as filename.
 *   - p: the directory of the file.
 *   - s: the source, either the 'tmp' directory or VFS.
 *
 * Copyright 2003-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('nologintasks' => true));

$vars = $injector->getInstance('Horde_Variables');

$file = basename($vars->f);
$source = strtolower($vars->get('s', 'tmp'));
$app_conf = strtolower($vars->get('c', 'horde'));
$name = $vars->get('n', $file);
$action = strtolower($vars->a);

switch ($source) {
case 'vfs':
    /* Change app if needed to get the right VFS config. */
    $pushed = $registry->pushApp($app_conf);

    /* Getting a file from Horde's VFS. */
    try {
        $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
        $file_data = $vfs->read($vars->p, $file);
    } catch (Horde_Vfs_Exception $e) {
        Horde::logMessage(sprintf('Error displaying image [%s]: %s', $vars->p . '/' . $file, $e->getMessage()), 'ERR');
        exit;
    }

    /* Return the original app if changed previously. */
    if ($pushed) {
        $registry->popApp($app_conf);
    }
    break;

case 'tmp':
    /* Getting a file from Horde's temp dir. */
    $tmpdir = Horde::getTempDir();
    if (empty($action) || ($action == 'resize')) {
        /* Use original if no action or if resizing. */
        $file_name = $tmpdir . '/' . $file;
    } else {
        $file_name = $tmpdir . '/mod_' . $file;
        if (!file_exists($file_name)) {
            copy($tmpdir . '/' . $file, $file_name);
        }
    }
    if (!file_exists($file_name)) {
        Horde::logMessage(sprintf('Image not found [%s]', $file_name), 'ERR');
        exit;
    }
    $file_data = file_get_contents($file_name);
    break;
}

/* Load the image object. */
$type = Horde_Mime_Magic::analyzeData($file_data);
$image = $injector->getInstance('Horde_Core_Factory_Image')->create(array(
    'data' => $file_data,
    'type' => $type
));

/* Check if no editing action required and send the image to browser. */
if (empty($action)) {
    $image->display();
    exit;
}

/* Image editing required. */
switch ($action) {
case 'rotate':
    $image->rotate($vars->v);
    break;

case 'flip':
    $image->flip();
    break;

case 'mirror':
    $image->mirror();
    break;

case 'grayscale':
    $image->grayscale();
    break;

case 'resize':
    list($width, $height, $ratio) = explode('.', $vars->v);

    /* If no width or height has been passed, get the original
     * ones. */
    if (empty($width) || empty($height)) {
        $orig = $image->getDimensions();
    }
    if (empty($width)) {
        $width = $orig['width'];
    }
    if (empty($height)) {
        $height = $orig['height'];
    }

    $image->resize($width, $height, $ratio);

    /* Since the original is always used for resizing make sure the
     * write is to 'mod_'. */
    $file_name = $tmpdir . '/mod_' . $file;
    break;
}

/* Write out any changes to the temporary file. */
file_put_contents($file_name, $image->raw());

$image->display();
