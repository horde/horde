#!/usr/bin/env php
<?php
/**
* This script interfaces with Ansel via the command-line
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Vijay Mahrra <webmaster@stain.net>
*/

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('ansel', array('cli' => true));

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(),
                              'hu:p:lc:g:a:d:t:r',
                              array('help', 'username=', 'password=', 'list',
                                    'create=', 'gallery=', 'add=', 'dir=',
                                    'caption=', 'reset='));

if ($ret instanceof PEAR_Error) {
    $error = _("Couldn't read command-line options.");
    Horde::logMessage($error, 'DEBUG');
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
    case 'r':
    case '--reset':
        $resetting = true;
        $resetType = $optValue;
        if (!empty($resetType)) {
            switch ($resetType) {
            case 'stacks':
            case 'thumbs':
                break;
            default:
                Horde:fatal(_("Invalid image type. Must be \"stacks\" or \"thumbs\""));
            }
        }
        break;
    }
}

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();
    if (!$auth->authenticate($username, array('password' => $password))) {
        $error = _("Login is incorrect.");
        Horde::logMessage($error, 'ERR');
        $cli->fatal($error);
    } else {
        $msg = sprintf(_("Logged in successfully as \"%s\"."), $username);
        Horde::logMessage($msg, 'DEBUG');
        $cli->message($msg, 'cli.success');
    }
}

if (!empty($resetting)) {
    if (!$registry->isAdmin()) {
        Horde::fatal(_("Requires admin access."));
    }
    $cli->message(_("Resetting thumbnails: "), 'cli.info');
    $galleries = $injector->getInstance('Ansel_Storage')->listGalleries(array('perm' => null));
    foreach ($galleries as $gallery) {
        if (empty($resetType) || $resetType == 'stacks') {
            $gallery->clearStacks();
            $cli->message(sprintf(_("Successfully reset stack cache for gallery: %d"), $gallery->getId()), 'cli.success');

        }
        if (empty($resetType) || $resetType == 'thumbs') {
            $gallery->clearViews();
            $cli->message(sprintf(_("Successfully reset image cache for gallery: %d"), $gallery->getId()), 'cli.success');
        }
    }

    $cli->message(_("Done."), 'cli.success');
    exit;
}

// Choose the gallery to add to (or use the created one).
if (!empty($galleryId)) {
    if (!$GLOBALS['injector']->getInstance('Ansel_Storage')->galleryExists($galleryId)) {
        $error = sprintf(_("Invalid gallery \"%s\" specified."), $galleryId);
        Horde::logMessage($error, 'WARN');
        $cli->fatal($error);
    } else {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($galleryId);
        if (!$gallery->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $error = sprintf(_("Access denied adding photos to \"%s\"."), $galleryId);
            Horde::logMessage($error, 'WARN');
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
    try {
        $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->createGallery($attributes, null, $parent);
    } catch (Ansel_Exception $e) {
        $galleryId = null;
        $error = sprintf(_("The gallery \"%s\" couldn't be created: %s"),
                         $gallery_name, $gallery->getMessage());
        Horde::logMessage($error, 'ERR');
        $cli->fatal($error);
    }
    $msg = sprintf(_("The gallery \"%s\" was created successfully."), $gallery_name);
    Horde::logMessage($msg, 'DEBUG');
    $cli->message($msg, 'cli.success');
}

// List galleries/images.
if (!empty($list)) {
    if (!empty($gallery)) {
        $images = $gallery->listImages();
        $cli->message(sprintf(_("Listing photos in %s"), $gallery->get('name')), 'cli.success');
        $cli->writeln();

        $images = array_keys($images);
        foreach ($images as $id) {
            $image = $GLOBALS['injector']->getInstance('Ansel_Storage')->getImage($id);
            $cli->writeln(str_pad($image->filename, 30) . $image->getVFSPath() . '/' . $id);
        }
    } else {
        $galleries = $GLOBALS['injector']->getInstance('Ansel_Storage')->listGalleries();
        $cli->message(_("Listing Gallery/Name"), 'cli.success');
        $cli->writeln();
        foreach ($galleries as $gallery) {
            $name = $gallery->get('name');
            $id = $gallery->getId();
            $msg = "$id/$name";
            $cli->writeln($msg);
            Horde::logMessage($msg, 'DEBUG');
        }
    }
}

// Add an image from the filesystem.
if (!empty($file) && isset($gallery)) {
    try {
        $image = Ansel::getImageFromFile($file, array('caption' => $caption));
        $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
        $image_id = $gallery->addImage($image);
    } catch (Ansel_Exception $e) {
        $error = sprintf(_("There was a problem adding the photo \"%s\" to gallery \"%s\": %s"),
                         basename($file), $galleryId, $e->getMessage());
        Horde::logMessage($error, 'ERR');
        $cli->fatal($error);
    }
    $msg = sprintf(_("Successfully added photo \"%s\" to gallery \"%s\"."), basename($file), $galleryId);
    $cli->message($msg, 'cli.success');
    Horde::logMessage($msg, 'NOTICE');
}

// Add all images from a directory on the filesystem.
if (!empty($dir) && isset($gallery)) {
    $msg = addDirToGallery($dir, $gallery);
    if ($msg) {
        $msg = sprintf(ngettext("Successfully added %d photo (%s) to gallery \"%s\" from \"%s\".", "Successfully added %d photos (%s) to gallery \"%s\" from \"%s\".", count($msg)),
                       count($msg), join(', ', $msg), $galleryId, $dir);
        $cli->message($msg, 'cli.success');
        Horde::logMessage($msg, 'NOTICE');
    } else {
        $msg = sprintf(_("The directory \"%s\" had no valid photos."), $dir);
        $cli->message($msg, 'cli.warning');
        Horde::logMessage($msg, 'WARN');
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
        Horde::logMessage($error, 'ERR');
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
        Horde::logMessage($error, 'ERR');
        return PEAR::raiseError($error);
    }
    chdir($dir);

    // Process each file and upload to the gallery.
    $added_images = array();
    foreach ($files_array as $file) {
        try {
            $image = Ansel::getImageFromFile($dir . '/' . $file);
            $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
            $image_id = $gallery->addImage($image);
        } catch (Ansel_Exception $e) {
            Horde::logMessage($e->getMessage(), 'WARN');
            $cli->message($image->getMessage(), 'cli.error');
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
    $cli->writeln(_("-h, --help                    Show this help"));
    $cli->writeln(_("-l, --list                    List galleries or photos (if combined with -g)"));
    $cli->writeln(_("-c, --create[=name/description/owner]\n      Create gallery (and use it)  Combined with -g to create a subgallery."));
    $cli->writeln(_("-g, --gallery[=shortname]     Select gallery to use"));
    $cli->writeln(_("-a, --add[=filename]          Add local file to selected gallery"));
    $cli->writeln(_("-d, --dir[=directory]         Add all files from the directory to the selected gallery"));
    $cli->writeln(_("-u, --username[=username]     Horde login username"));
    $cli->writeln(_("-p, --password[=password]     Horde login password"));
    $cli->writeln(_("-t, --caption[=caption]       Caption for photo (if combined with -a)"));
    $cli->writeln(_("-r, --reset[=[thumbs|stacks]]\n      Reset generated images, optionally only a certain type. Script must be run as root or another system user with enough permissions to the VFS to delete."));
    $cli->writeln();
}
