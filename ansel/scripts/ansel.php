#!/usr/bin/php
<?php
/**
* This script interfaces with Ansel via the command-line
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Vijay Mahrra <webmaster@stain.net>
*/

// Do CLI checks and environment setup first.
require_once dirname(__FILE__) . '/../../lib/base.load.php';
require_once HORDE_BASE . '/lib/core.php';

// Make sure no one runs this from the web.
if (!Horde_Cli::runningFromCLI()) {
    exit("Must be run from the command line\n");
}

// Load the CLI environment.
Horde_Cli::init();
$cli = Horde_Cli::singleton();

// Load Ansel.
$ansel_authentication = 'none';
require_once ANSEL_BASE . '/lib/base.php';

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(),
                              'hu:p:lc:g:a:d:t:',
                              array('help', 'username=', 'password=', 'list',
                                    'create=', 'gallery=', 'add=', 'dir=',
                                    'caption='));

if (is_a($ret, 'PEAR_Error')) {
    var_dump($ret);
    $error = _("Couldn't read command-line options.");
    Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_DEBUG);
    $cli->fatal($error);
}

// Show help and exit if no arguments were set.
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

// Defaults/initialization.
$caption = '';

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

    case 'l':
    case '--list':
        $list = true;
        break;

    case 'c':
    case '--create':
        list($gallery_name, $gallery_desc, $gallery_owner) = explode('/', $optValue, 4);
        $createGallery = true;
        break;

    case 'g':
    case '--gallery':
        $galleryId = $optValue;
        break;

    case 'a':
    case '--add':
        $file = $optValue;
        break;

    case 'd':
    case '--dir':
        $dir = $optValue;
        break;

    case 'h':
    case '--help':
        showHelp();
        exit;

    case 't':
    case '--caption':
        $caption = $optValue;
        break;
    }
}

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = Horde_Auth::singleton($conf['auth']['driver']);
    if (!$auth->authenticate($username, array('password' => $password))) {
        $error = _("Login is incorrect.");
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        $cli->fatal($error);
    } else {
        $msg = sprintf(_("Logged in successfully as \"%s\"."), $username);
        Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $cli->message($msg, 'cli.success');
    }
}

// Choose the gallery to add to (or use the created one).
if (!empty($galleryId)) {
    if (!$ansel_storage->galleryExists($galleryId)) {
        $error = sprintf(_("Invalid gallery \"%s\" specified."), $galleryId);
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_WARNING);
        $cli->fatal($error);
    } else {
        $gallery = $ansel_storage->getGallery($galleryId);
        if (is_a($gallery, 'PEAR_Error') ||
            !$gallery->hasPermission(Horde_Auth::getAuth(), Horde_Perms::EDIT)) {
            $error = sprintf(_("Access denied adding photos to \"%s\"."),
                             $galleryId);
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_WARNING);
            $cli->fatal($error);
        }
    }
}

// Create a gallery.
if (!empty($createGallery)) {

    // See if we have selected a gallery parent.
    if (!empty($galleryId)) {
        $parent = $galleryId;
    }

    $attributes = array('name' => $gallery_name,
                        'desc' => $gallery_desc,
                        'owner' => $gallery_owner);
    $gallery = $ansel_storage->createGallery($attributes, null, $parent);
    if (is_a($gallery, 'PEAR_Error')) {
        $galleryId = null;
        $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"),
                         $gallery_name, $gallery->getMessage());
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        $cli->fatal($error);
    } else {
        $msg = sprintf(_("The gallery \"%s\" was created successfully."),
                       $gallery_name);
        Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        $cli->message($msg, 'cli.success');
    }
}

// List galleries/images.
if (!empty($list)) {
    if (!empty($gallery)) {
        $images = $gallery->listImages();
        if (is_a($images, 'PEAR_Error')) {
            $cli->fatal($images->getMessage());
        }

        $cli->message(sprintf(_("Listing photos in %s"), $gallery->get('name')), 'cli.success');
        $cli->writeln();

        $images = array_keys($images);
        foreach ($images as $id) {
            $image = &$ansel_storage->getImage($id);
            $cli->writeln(str_pad($image->filename, 30) . $image->getVFSPath() . '/' . $id);
        }
    } else {
        $galleries = $GLOBALS['ansel_storage']->listGalleries();
        if (is_a($galleries, 'PEAR_Error')) {
            $error = _("Couldn't list galleries.");
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_DEBUG);
            $cli->fatal($error);
        }

        $cli->message(_("Listing Gallery/Name"), 'cli.success');
        $cli->writeln();

        foreach ($galleries as $id => $gallery) {
            $name = $gallery->get('name');
            $msg = "$id/$name";
            $cli->writeln($msg);
            Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_DEBUG);
        }
    }
}

// Add an image from the filesystem.
if (!empty($file) && isset($gallery) && !is_a($gallery, 'PEAR_Error')) {
    $image = &Ansel::getImageFromFile($file, array('caption' => $caption));
    if (is_a($image, 'PEAR_Error')) {
        Horde::logMessage($image->getMessage(), __FILE__, __LINE__, PEAR_LOG_WARNING);
        $cli->fatal($image->getMessage());
    }

    $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
    $image_id = $gallery->addImage($image);
    if (is_a($image_id, 'PEAR_Error')) {
        $error = sprintf(_("There was a problem adding the photo \"%s\" to gallery \"%s\": %s"),
                         basename($file), $galleryId, $image_id->getMessage());
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        $cli->fatal($error);
    }

    $msg = sprintf(_("Successfully added photo \"%s\" to gallery \"%s\"."),
                   basename($file), $galleryId);
    $cli->message($msg, 'cli.success');
    Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_NOTICE);
}

// Add all images from a directory on the filesystem.
if (!empty($dir) && isset($gallery) && !is_a($gallery, 'PEAR_Error')) {
    $msg = addDirToGallery($dir, $gallery);
    if (is_a($msg, 'PEAR_Error')) {
        Horde::logMessage($msg->getMessage(), __FILE__, __LINE__, PEAR_LOG_ERR);
        $cli->fatal($msg->getMessage());
    }
    if ($msg) {
        $msg = sprintf(ngettext("Successfully added %d photo (%s) to gallery \"%s\" from \"%s\".", "Successfully added %d photos (%s) to gallery \"%s\" from \"%s\".", count($msg)),
                       count($msg), join(', ', $msg), $galleryId, $dir);
        $cli->message($msg, 'cli.success');
        Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_NOTICE);
    } else {
        $msg = sprintf(_("The directory \"%s\" had no valid photos."), $dir);
        $cli->message($msg, 'cli.warning');
        Horde::logMessage($msg, __FILE__, __LINE__, PEAR_LOG_WARNING);
    }
}

exit;


/**
 * Reads all images from a directory into the currently selected gallery.
 *
 * @param string $dir
 * @param object $gallery &$ansel_shares->getShare($galleryId)
 * @return array An array of all the images that were successfully added to
 *               the gallery, or PEAR_Error
 */
function addDirToGallery($dir = '', &$gallery)
{
    global $cli, $galleryId, $ansel_shares;

    if (!file_exists($dir)) {
        $error = sprintf(_("The directory \"%s\" doesn't exist."), $dir);
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        return PEAR::raiseError($error);
    }

    // Read all the files into an array.
    $files_array = array();
    $h = opendir($dir);
    while (false !== ($file = readdir($h))) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $files_array[] = $file;
    }
    closedir($h);

    if (!$files_array) {
        $error = sprintf(_("The directory \"%s\" is empty."), $dir);
        Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
        return PEAR::raiseError($error);
    }
    chdir($dir);

    // Process each file and upload to the gallery.
    $added_images = array();
    foreach ($files_array as $file) {
        $image = Ansel::getImageFromFile($dir . '/' . $file);
        if (is_a($image, 'PEAR_Error')) {
            Horde::logMessage($image->getMessage(), __FILE__, __LINE__, PEAR_LOG_WARNING);
            $cli->message($image->getMessage(), 'cli.error');
            continue;
        }

        $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
        $image_id = $gallery->addImage($image);
        if (is_a($image_id, 'PEAR_Error')) {
            $error = sprintf(_("There was a problem adding the photo \"%s\" to gallery \"%s\"."),
                             $file, $galleryId);
            Horde::logMessage($error, __FILE__, __LINE__, PEAR_LOG_ERR);
            $cli->message($image_id->getMessage(), 'cli.error');
            continue;
        }

        $added_images[] = $file;
    }

    return $added_images;
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
    $cli->writeln(_("-l, --list                   List galleries or photos (if combined with -g)"));
    $cli->writeln(_("-c, --create[=name/description/owner]\n                             Create gallery (and use it)  Combined with -g to create a subgallery."));
    $cli->writeln(_("-g, --gallery[=shortname]    Select gallery to use"));
    $cli->writeln(_("-a, --add[=filename]         Add local file to selected gallery"));
    $cli->writeln(_("-d, --dir[=directory]        Add all files from the directory to the selected\n                             gallery"));
    $cli->writeln(_("-u, --username[=username]    Horde login username"));
    $cli->writeln(_("-p, --password[=password]    Horde login password"));
    $cli->writeln(_("-t, --caption[=caption]      Caption for photo (if combined with -a)"));
    $cli->writeln();
}
