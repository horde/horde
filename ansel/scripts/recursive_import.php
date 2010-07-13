#!/usr/bin/php -q
<?php
/**
* This script interfaces with Ansel via the command-line
*
* See the enclosed file COPYING for license information (GPL). If you
* did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
*
* @author Vijay Mahrra <webmaster@stain.net>
*/

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('ansel', array('authentication' => 'none', 'cli' => true));

// We accept the user name on the command-line.
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'hu:p:lc:g:a:d:k',
                              array('help', 'username=', 'password=', 'dir=', 'keep'));

if ($ret instanceof PEAR_Error) {
    $cli->fatal($ret->getMessage());
}

// Show help and exit if no arguments were set.
list($opts, $args) = $ret;
if (!$opts) {
    showHelp();
    exit;
}

// Delete empty galleries by default
$keepEmpties = false;

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
    }
}

// Login to horde if username & password are set.
if (!empty($username) && !empty($password)) {
    $auth = $injector->getInstance('Horde_Auth')->getAuth();
    if (!$auth->authenticate($username, array('password' => $password))) {
        $cli->fatal(_("Username or password is incorrect."));
    } else {
        $cli->message(sprintf(_("Logged in successfully as \"%s\"."), $username), 'cli.success');
    }
} else {
    $cli->fatal(_("You must specify a valid username and password."));
}

if (empty($dir)) {
    $cli->fatal(_("You must specify a valid directory."));
}

$registry->setCharset('utf-8');
$gallery_id = processDirectory($dir);
if (!$keepEmpties) {
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery_id);
    emptyGalleryCheck($gallery);
}
exit;

/**
 * Check for, and remove any empty galleries that may have been created during
 * import.
 *
 */
function emptyGalleryCheck($gallery)
{
    if ($gallery->hasSubGalleries()) {
        $children = $GLOBALS['injector']
            ->getInstance('Ansel_Storage')
            ->getScope()
            ->listGalleries(array('parent' => $gallery));
        foreach ($children as $child) {
            // First check all children to see if they are empty...
            emptyGalleryCheck($child);
            if (!$child->countImages() && !$child->hasSubGalleries()) {
                $result = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->removeGallery($child);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $child->get('name')), 'cli.success');
            }

            // Refresh the gallery values since we mucked around a bit with it
            $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->getGallery($gallery->getId());
            // Now that any empty children are removed, see if we are empty
            if (!$gallery->countImages() && !$gallery->hasSubGalleries()) {
                $result = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->removeGallery($gallery);
                $GLOBALS['cli']->message(sprintf(_("Deleting empty gallery, \"%s\""), $gallery->get('name')), 'cli.success');
            }
        }
    }
}
/**
 * Read all images from a directory into the currently selected
 * gallery.
 *
 * @param string $dir  The directory to create a gallery for and import.
 * @param integer $parent  Parent gallery id to attach the new gallery to.
 *
 * @return mixed  The gallery_id of the newly created gallery || PEAR_Error
 */
function processDirectory($dir, $parent = null)
{
    global $cli;

    $dir = Horde_Util::realPath($dir);
    if (!is_dir($dir)) {
        $cli->fatal(sprintf(_("\"%s\" is not a directory."), $dir));
    }

    // Create a gallery for this directory level.
    $name = basename($dir);
    $cli->message(sprintf(_("Creating gallery: \"%s\""), $name), 'cli.message');
    $gallery = $GLOBALS['injector']->getInstance('Ansel_Storage')->getScope()->createGallery(array('name' => $name), null, $parent);
    $cli->message(sprintf(_("The gallery \"%s\" was created successfully."), $name), 'cli.success');

    // Read all the files into an array.
    $files = array();
    $directories = array();
    $h = opendir($dir);
    while (false !== ($entry = readdir($h))) {
        if ($entry == '.' || $entry == '..') {
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
            $image = Ansel::getImageFromFile($dir . '/' . $file);
            $cli->message(sprintf(_("Storing photo \"%s\"..."), $file), 'cli.message');
            $image_id = $gallery->addImage($image);
            $added_images[] = $file;
        }

        $cli->message(sprintf(ngettext("Successfully added %d photo (%s) to gallery \"%s\" from \"%s\".", "Successfully added %d photos (%s) to gallery \"%s\" from \"%s\".", count($added_images)),
                              count($added_images), join(', ', $added_images), $gallery->get('name'), $dir), 'cli.success');
    }

    if ($directories) {
        $cli->message(_("Adding subdirectories:"), 'cli.message');
        foreach ($directories as $directory) {
            processDirectory($dir . '/' . $directory, $gallery->id);
        }
    }

    return $gallery->getId();
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
    $cli->writeln(_("-k, --keep                   Do not delete empty galleries after import is complete."));
    $cli->writeln();
}
