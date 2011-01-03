<?php
/**
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('luxor');

function printdir($dir)
{
    $view = new Horde_View(array('templatePath' => LUXOR_TEMPLATES));
    $view->addHelper('Text');
    $dirlist = Luxor::dirExpand($dir);
    if (is_a($dirlist, 'PEAR_Error')) {
        $GLOBALS['notification']->push($dirlist, 'horde.error');
        return;
    }
    $view->files = $dirlist;

    return $view->render('directory.html.php')
        . Luxor::dirDesc($GLOBALS['files'], $dir);
}

function printfile($pathname, $raw = false)
{
    if (substr($pathname, -1) == '/') {
        return printdir($pathname);
    }

    if (Luxor::isRestricted($pathname)) {
        $GLOBALS['notification']->push(sprintf(_("You do not have permission to view %s."), $pathname), 'horde.error');
        return '';
    }

    $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
    $lastmod = $GLOBALS['index']->getLastModified($pathname);
    $key = 'luxor_' . $pathname;
    if ($raw) {
        $key .= '_raw';
    }
    $output = $cache->get($key, time() - $lastmod);
    if (true || !$output) {
        $output = $raw ? printfile_raw($pathname) : printfile_markup($pathname);
        if (!empty($output)) {
            $cache->set($key, $output);
        }
    }

    return $output;
}

function printfile_markup($pathname)
{
    $ann = $GLOBALS['files']->getAnnotations($pathname);
    /*  Commented out until we have a driver that supports annotations.
        this formatting should probably be done in markupFile();
        if (is_array($ann)) {
            $b = null;
            for ($i = 0; $i < count($ann); $i++) {
                if ($ann[$i] == $b) {
                    $ann[$i] = str_repeat(' ', 16);
                    continue;
                }
                $b = $ann[$i];
                $ann[$i] .= str_repeat(' ', 6 - strlen($ann[$i])) . $files->getAuthor($pathname);
                $ann[$i] .= str_repeat(' ', 16 - strlen($ann[$i]));
            }
        }
    */

    $fileh = $GLOBALS['files']->getFileHandle($pathname);
    if (!$fileh) {
        $GLOBALS['notification']->push(sprintf(_("The file %s can't be opened or doesn't exist."), $pathname), 'horde.error');
        return;
    }

    $output = Luxor::markupFile($pathname, $fileh, $ann);
    if ($output === false) {
        $GLOBALS['notification']->push(sprintf(_("Could not markup file %s."), $pathname), 'horde.warning');
        return printfile_raw($pathname);
    }

    return $output;
}

function printfile_raw($pathname)
{
    global $mime_drivers, $mime_drivers_map;

    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
    extract($result);
    $result = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'luxor');
    if (isset($result['mime_drivers'])) {
        $mime_drivers = Horde_Array::replaceRecursive(
            $mime_drivers, $result['mime_drivers']);
    }
    if (isset($result['mime_drivers_map'])) {
        $mime_drivers_map = Horde_Array::replaceRecursive(
            $mime_drivers_map, $result['mime_drivers_map']);
    }

    $filename = $GLOBALS['files']->toReal($pathname);
    $data = file_get_contents($filename);

    $mime_part = new Horde_Mime_Part(Horde_Mime_Magic::filenameToMime($pathname), $data);
    $mime_part->setName($pathname);
    $viewer = $GLOBALS['injector']->getInstance('Horde_Core_Factory_MimeViewer')->create($mime_part);

    if ($viewer->getType() == 'text/plain') {
        return '<pre class="fixed">' . htmlspecialchars($viewer->render()) . '</pre>';
    } else {
        return $viewer->render();
    }
}

if (substr($pathname, -5) == '.html' ||
    substr($pathname, -4) == '.htm' ||
    Horde_Util::getFormData('raw')) {
    echo printfile($pathname, true);
    exit;
}

$content = printfile($pathname);

if (substr($pathname, -1) == '/') {
    $title = sprintf(_("Directory Listing :: %s"), $pathname);
    Horde::addScriptFile('tables.js', 'horde', true);
} else {
    $title = sprintf(_("Markup of %s"), $pathname);
    $lastmod = $index->getLastModified($pathname);
    if ($lastmod) {
        $mod_gmt = gmdate('D, d M Y H:i:s', $lastmod) . ' GMT';
        header('Last-Modified: ' . $mod_gmt);
        header('Cache-Control: public, max-age=86400');
    }

    if (!empty($conf['options']['use_show_var'])) {
        Horde::addScriptFile('show_var.js', 'luxor', true);
    }
}

if (is_a($content, 'PEAR_Error')) {
    $notification->push($content->getMessage(), 'horde.error');
}

require $registry->get('templates', 'horde') . '/common-header.inc';
require LUXOR_TEMPLATES . '/menu.inc';
require LUXOR_TEMPLATES . '/headerbar.inc';
if (!is_a($content, 'PEAR_Error')) {
    echo $content;
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
