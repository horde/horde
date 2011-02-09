#!/usr/bin/env php
<?php
/**
* This script allows for adding images to an Ansel install using an RPC
* interface. This script requires Horde's CLI and RPC libraries along with
* PEAR's Console_Getopt library.  You will need to make sure that those
* libraries reside somewhere in PHP's include path.
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Michael J. Rubinsky <mrubinsk@horde.org>
*/

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel', array('cli' => true));

/* Command line options */
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu:p:g:s:d:kr:zl',
                              array('help', 'username=', 'password=', 'gallery=', 'slug=', 'dir=', 'keep', 'remotehost=', 'gzip', 'lzf'));

if ($ret instanceof PEAR_Error) {
    $cli->fatal($ret->getMessage());
}

/* Show help and exit if no arguments were set. */
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

/* Delete empty galleries by default */
$keepEmpties = false;

/* Assume we are creating a new gallery */
$gallery_id = null;
$gallery_slug = null;
$useCompression = 'none';

foreach ($opts as $opt) {
    list($optName, $optValue) = $opt;
    switch ($optName) {
    case 'u':
    case '--username':
        $username = $optValue;
        break;

    case 'p':
    case '--password':
        $password = $optValue;
        break;

    case 'd':
    case '--dir':
        $dir = $optValue;
        break;

    case 'h':
    case '--help':
        showHelp();
        exit;

    case 'k':
    case '--keep':
        $keepEmpties = true;
        break;
    case 'r':
    case '--remotehost':
        $rpc_endpoint = $optValue;
        break;
    case 'g':
    case '--gallery':
        $gallery_id = $optValue;
        break;
    case 's':
    case '--slug':
        $gallery_slug = $optValue;
        break;
    case 'z':
    case '--gzip':
        $useCompression = 'gzip';
        break;
    case 'l':
    case 'lzf':
        $useCompression = 'lzf';
        break;
    }
}

/* Sanity checks */
if (!empty($username) && !empty($password)) {
    $rpc_auth = array(
        'username' => $username,
        'password' => $password);
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (empty($rpc_endpoint)) {
    $cli->fatal(_("You must specify the url for the remote Horde RPC server."));
}

if (empty($dir)) {
    $cli->fatal(_("You must specify a valid directory."));
}

processDirectory($dir, null, $gallery_id, $gallery_slug, $useCompression);

/**
 * Check for, and remove any empty galleries that may have been created during
 * import.
 */
function emptyGalleryCheck($gallery)
{
    if ($gallery->hasSubGalleries()) {
        $children = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->listGalleries(array('parent' => $gallery));
        foreach ($children as $child) {
            // First check all children to see if they are empty...
            emptyGalleryCheck($child);
            if (!$child->countImages() && !$child->hasSubGalleries()) {
                $result = $GLOBALS['injector']->getInstance('Ansel_Storage')->removeGallery($child);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $child->get('name')), 'cli.success');
            }

            // Refresh the gallery values since we mucked around a bit with it
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($gallery->getId());
            // Now that any empty children are removed, see if we are empty
            if (!$gallery->countImages() && !$gallery->hasSubGalleries()) {
                $result = $GLOBALS['injector']->getInstance('Ansel_Storage')->removeGallery($gallery);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $gallery->get('name')), 'cli.success');
            }
        }
    }
}

/**
 * Read all images from a directory into the currently selected gallery.
 *
 * @param string $dir          The directory to create a gallery for and import.
 * @param integer $parent      Parent gallery id to attach the new gallery to.
 * @param integer $gallery_id  Start at this gallery_id.
 * @param string  $slug        Same as $gallery_id, except use this slug
 *
 * @return mixed  The gallery_id of the newly created gallery || PEAR_Error
 */
function processDirectory($dir, $parent = null, $gallery_id = null, $slug = null, $compress = 'none')
{
    global $cli, $rpc_auth, $rpc_endpoint;

    $dir = Horde_Util::realPath($dir);
    if (!is_dir($dir)) {
        $cli->fatal(sprintf(_("\"%s\" is not a directory."), $dir));
    }
    $rpc_params = $rpc_auth;
    $language = isset($GLOBALS['language']) ? $GLOBALS['language'] :
            (isset($_SERVER['LANG']) ? $_SERVER['LANG'] : '');
    if (!empty($language)) {
        $rpc_params['request.headers'] = array('Accept-Language' => $language);
    }
    $http = $GLOBALS['injector']->createInstance('Horde_Core_Factory_Http_Client')->create($rpc_params);
    /* Create a gallery or use an existing one? */
    if (!empty($gallery_id) || !empty($slug)) {
        /* Start with an existing gallery */
        $method = 'images.getGalleries';
        $params = array(
            is_null($gallery_id) ? null : array($gallery_id),
            null,
            is_null($slug) ? null : array($slug),
        );
        $result = Horde_Rpc::request('jsonrpc', $rpc_endpoint, $method, $http, $params);
        $result = $result->result;
        if (empty($result)) {
            $cli->fatal(sprintf(_("Gallery %s not found."), (empty($slug) ? $gallery_id : $slug)));
        }

        /* Should have only one here, but jsonrpc returns an object, not array */
        foreach ($result as $gallery_info) {
           $name = $gallery_info->attribute_name;
           $gallery_id = $gallery_info->share_id;
        }
        if (empty($name)) {
            $cli->fatal(sprintf(_("Gallery %s not found."), (empty($slug) ? $gallery_id : $slug)));
        }
    } else {
        /* Creating a new gallery */
        $name = basename($dir);
        $cli->message(sprintf(_("Creating gallery: \"%s\""), $name), 'cli.message');
        $method = 'images.createGallery';
        $params = array(null, array('name' => $name), null, $parent);
        $result = Horde_Rpc::request('jsonrpc', $rpc_endpoint, $method, $http, $params);
        $gallery_id = $result->result;
        $cli->message(sprintf(_("The gallery \"%s\" was created successfully."), $name), 'cli.success');
    }

    /* Get the files and directories */
    $files = array();
    $directories = array();
    $h = opendir($dir);
    while (false !== ($entry = readdir($h))) {
        if ($entry == '.' ||
            $entry == '..' ||
            $entry == 'Thumbs.db' ||
            $entry == '.DS_Store' ||
            $entry == '.localized' ||
            $entry == '__MACOSX' ||
            strpos($entry, '.') === 1) {
            continue;
        }

        if (is_dir($dir . '/' . $entry)) {
            $directories[] = $entry;
        } else {
            $files[] = $entry;
        }
    }
    closedir($h);

    if ($files) {
        chdir($dir);

        // Process each file and upload to the gallery.
        $added_images = array();
        foreach ($files as $file) {
            $image = getImageFromFile($dir . '/' . $file, $compress);
            $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
            $method = 'images.saveImage';
            $params = array(null, $gallery_id, $image, false, null, 'binhex', $slug, $compress);
            $result = Horde_Rpc::request('jsonrpc', $rpc_endpoint, $method, $http, $params);
            if (!($result instanceof stdClass)) {
                $cli->fatal(sprintf(_("There was an unspecified error. The server returned: %s"), print_r($result, true)));
            }
            $image_id = $result->result;
            $added_images[] = $file;
        }

        $cli->message(sprintf(ngettext("Successfully added %d photo (%s) to gallery \"%s\" from \"%s\".", "Successfully added %d photos (%s) to gallery \"%s\" from \"%s\".", count($added_images)),
                              count($added_images), join(', ', $added_images), $name, $dir), 'cli.success');
    }

    if ($directories) {
        $cli->message(_("Adding subdirectories:"), 'cli.message');
        foreach ($directories as $directory) {
            processDirectory($dir . '/' . $directory, $gallery_id);
        }
    }

    return $gallery_id;
}

/**
 * Read an image from the filesystem.
 *
 * @param string $file     The filename of the image.
 *
 * @return array  The image data of the file as an array or PEAR_Error
 */
function getImageFromFile($file, $compress = 'none')
{
    if (!file_exists($file)) {
        return PEAR::raiseError(sprintf(_("The file \"%s\" doesn't exist."),
                                $file));
    }

    global $conf, $cli;

    // Get the mime type of the file (and make sure it's an image).
    $mime_type = Horde_Mime_Magic::analyzeFile($file, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
    if (strpos($mime_type, 'image') === false) {
        return PEAR::raiseError(sprintf(_("Can't get unknown file type \"%s\"."), $file));
    }

    if ($compress == 'gzip' && Horde_Util::loadExtension('zlib')) {
        $data = gzcompress(file_get_contents($file));
    } elseif ($compress == 'gzip') {
        $cli->fatal(_("Could not load the gzip extension"));
    } elseif ($compress == 'lzf' && Horde_Util::loadExtension('lzf')) {
        $data = lzf_compress(file_get_contents($file));
    } elseif ($compress == 'lzf') {
        $cli->fatal(_("Could not load the lzf extension"));
    } else {
        $data = file_get_contents($file);
    }

    $image = array('filename' => basename($file),
                   'description' => '',
                   'type' => $mime_type,
                   'data' => bin2hex($data),
                   );

    return $image;
}



/**
 * Show the command line arguments that the script accepts.
 */
function showHelp()
{
    global $cli;

    $cli->writeln(sprintf(_("Usage: %s [OPTIONS]..."), basename(__FILE__)));
    $cli->writeln();
    $cli->writeln(_("Mandatory arguments to long options are mandatory for short options too."));
    $cli->writeln();
    $cli->writeln(_("-h, --help                   Show this help"));
    $cli->writeln(_("-d, --dir[=directory]        Recursively add all files from the directory, creating\n                             a gallery for each directory"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln(_("-g, --gallery[=gallery_id]   The gallery id to add directory contents to"));
    $cli->writeln(_("-s, --slug[=gallery_slug]    The gallery slug to add directory contents to"));
    //$cli->writeln(_("-k, --keep                   Do not delete empty galleries after import is complete."));
    $cli->writeln(_("-r, --remotehost[=url]       The url of the remote rpc server."));
}
