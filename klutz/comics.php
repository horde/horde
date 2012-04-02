<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('klutz');

// Get the name of the comic we want to see, if passed in.
$index = Horde_Util::getFormData('index');

// Make sure we have a valid date set.
$date = Horde_Util::getFormData('date');
if (!empty($date) && !is_numeric($date)) {
    $date = strtotime($date);
}

// Set default page title and date format.
$title = _("Today's Comics");
$date_format = 'D, d M Y H:i:s T';

// Figure out how many days of history we keep.
$daystokeep = 90;
if (!empty($conf['backend']['daystokeep'])) {
    $daystokeep = $conf['backend']['daystokeep'];
}

// Get/guess the current action ID.
$actionID = Horde_Util::getFormData('actionID', Horde_Util::nonInputVar('actionID'));
if (is_null($actionID)) {
    if (empty($date) && empty($index)) {
        $actionID = 'main';
    } elseif (empty($date) && !empty($index)) {
        $actionID = 'comic';
    } elseif (!empty($date) && empty($index)) {
        $actionID = 'day';
    } else {
        $actionID = 'image';
    }
}
if (empty($date)) {
    $date = mktime(0, 0, 0);
}

// If we're not displaying a raw image, require the common
// header file and perform some useful date calculations.
if ($actionID != 'image') {
    // Figure out timestamps for navigation links.
    $time = mktime(0, 0, 0);
    $t = getdate($time);
    $dates = $klutz_driver->listDates($time, mktime(0, 0, 0,
                                                    $t['mon'],
                                                    $t['mday'] - $daystokeep,
                                                    $t['year']));

    if (count($dates)) {
        $first_day = $dates[0];
    } else {
        $first_day = null;
    }

    $d = getdate($date);
    $prev_month = mktime(0, 0, 0, $d['mon'] - 1, 1, $d['year']);
    $next_month = mktime(0, 0, 0, $d['mon'] + 1, 1, $d['year']);
    $yesterday = mktime(0, 0, 0, $d['mon'], $d['mday'] - 1, $d['year']);
    $tomorrow = mktime(0, 0, 0, $d['mon'], $d['mday'] + 1, $d['year']);

    $url = Horde_Util::addParameter(Horde::selfUrl(false, false), 'actionID', $actionID);
    $prev_month_url = null;
    $yesterday_url = null;
    $tomorrow_url = null;
    $next_month_url = null;
    $comic_select = null;

    // See if we have the images api available
    // and we are allowed to select an image gallery
    $imageApp = $registry->hasMethod('images/saveImage');
    if ($imageApp && $prefs->isLocked('comicgallery') && $prefs->getValue('comicgallery') == '') {
        $imageApp = false;
    }
}

switch ($actionID) {
case 'image':
    $index = Horde_Util::getFormData('index');
    $image = $klutz_driver->retrieveImage($index, $date);
    if (is_object($image)) {
        header('Content-type: ' . $image->type);
        if ($image->lastmodified > 0) {
            header('Last-Modified: ' .
                   gmdate($date_format, $image->lastmodified));
        }
        header('Expires: ' . gmdate($date_format, time() + 172800));
        header('Cache-Control: public, max-age=172800');
        header('Pragma:');
        echo $image->data;
        exit;
    } elseif (is_string($image) && substr($image, 0, 4) == 'http') {
        header('Location: ' . $image);
        exit;
    } else {
        // Do some kind of error handling here.
    }
    break;

case 'main':
    $title = _("Main Listing");
    if ($prefs->getValue('show_unselected')) {
        $comics = $klutz->listEnabled();
    } else {
        // Get the list of comics to display.
        $comics = explode("\t", $prefs->getValue('viewcomics'));
        if (count($comics) == 1 && empty($comics[0])) {
            $comics = null;
        }
        $comics = $klutz->listEnabled($comics);
    }

    $selected = explode("\t", $prefs->getValue('viewcomics'));
    if (count($selected) == 1 && empty($selected[0])) {
        $selected = $comics;
    }

    $visited = explode("\t", $prefs->getValue('datesvisited'));
    if (count($visited) == 1 && empty($visited[0])) {
        $visited = array();
    }
    if (count($visited) > $daystokeep) {
        sort($visited, SORT_NUMERIC);
        $f = date('Ymd', $first_day);
        // Try the easy way first - find the actual date in the array
        $i = array_search($f, $visited);
        if ($i !== false) {
            // Note that this will remove up to the date searched for, not
            // that date itself
            array_splice($visited, 0, $i);
        } else {
            // guess we have to do it the slow/tedious way
            $c = count($visited);
            for ($i = 0; $i < $c; $i++) {
                if ($visited[$i] >= $f) break;
                unset($visited[$i]);
            }
        }
        $prefs->setValue('datesvisited', implode("\t", $visited));
    }

    $comics = $klutz->getProperty($comics, array('name', 'author'));

    // Date navigation.
    if (count($klutz_driver->listDates($prev_month))) {
        $prev_month_url = Horde_Util::addParameter($url, 'date', $prev_month);
    }
    if (count($klutz_driver->listDates($next_month))) {
        $next_month_url = Horde_Util::addParameter($url, 'date', $next_month);
    }
    $page_output->header(array(
        'title' => $title
    ));
    echo Horde::menu();
    require KLUTZ_TEMPLATES . '/comics/main.inc';
    break;

case 'day':
    $title = strftime('%B %d, %Y', $date);

    // Display the navbar.
    if (in_array($yesterday, $klutz_driver->listDates($yesterday))) {
        $yesterday_url = Horde_Util::addParameter($url, 'date', $yesterday);
    }
    if (in_array($tomorrow, $klutz_driver->listDates($tomorrow))) {
        $tomorrow_url = Horde_Util::addParameter($url, 'date', $tomorrow);
    }

    $page_output->header(array(
        'title' => $title
    ));
    echo Horde::menu();
    require KLUTZ_TEMPLATES . '/comics/nav_bar.inc';
    if (!empty($imageApp)) {
        $page_output->addScriptFile('popup.js', 'horde');
    }

    // Used for tracking dates we've already looked at.
    $today = date('Ymd', $date);

    // Get the list of comics to display.
    $comics = explode("\t", $prefs->getValue('viewcomics'));
    if (count($comics) == 1 && empty($comics[0])) {
        $comics = null;
    }

    // Update prefs to mark this date as visited.
    $visited = explode("\t", $prefs->getValue('datesvisited'));
    if (count($visited) == 1 && empty($visited[0])) {
        $visited = array();
    }
    if (!$prefs->isLocked('datesvisited') && !in_array($today, $visited)) {
        $visited[] = $today;
        $prefs->setValue('datesvisited', implode("\t", $visited));
    }

    foreach ($klutz->listEnabled($comics, $date) as $index) {
        $name = $klutz->getProperty($index, 'name');
        $author = $klutz->getProperty($index, 'author');
        $homepage = $klutz->getProperty($index, 'homepage');
        if ($klutz_driver->imageExists($index, $date)) {
            $size = $klutz_driver->imageSize($index, $date);
            $url = Horde_Util::addParameter(Horde::selfUrl(false, false), array('actionID' => 'image',
                                                                          'date' => $date,
                                                                          'index' => $index));

            // We have a comic, build a link to save to a gallery.
            if (!empty($imageApp)) {
                $popupUrl = Horde_Util::addParameter('savecomic.php', array('date' => $date,
                                                                      'index' => $index));
                $saveImgLink = Horde::link('#', _("Save Comic to Gallery"), null,
                                           null, 'popup(\'' . $popupUrl . '\', 450, 290); return false;') .
                                          '<img src="' . $registry->get('icon', $imageApp) . '" alt="' . _("Save Comic to Gallery") . '" /></a>';

            }

            require KLUTZ_TEMPLATES . '/comics/comic.inc';
        } elseif ($klutz->getProperty($index, 'days') != 'random') {
            // If it's not a "random"-type comic, display a missing message.
            require KLUTZ_TEMPLATES . '/comics/missing.inc';
        }
    }
    require KLUTZ_TEMPLATES . '/comics/nav_bar.inc';
    break;

case 'comic':
    // Get a list of the available comics the user reads
    $comics = explode("\t", $prefs->getValue('viewcomics'));
    if ((count($comics) == 1 && empty($comics[0])) ||
        $prefs->getValue('show_unselected') ||
        !in_array($index, $comics)) {
        $comics = null;
    }
    $comics = $klutz->listEnabled($comics);
    $i = array_search($index, $comics);
    if ($i === false) {
        $notification->push(_("This comic doesn't exist or is disabled."),
                            'horde.error');
        $url = Horde_Util::addParameter(Horde::selfUrl(false, false), array('date' => $date,
                                                                      'actionID' => 'main'));
        header('Location: ' . $url);
        exit;
    }

    $comic_select = '';
    if (count($comics)) {
        $prev_comic = $next_comic = null;
        foreach (array_keys($comics) as $c) {
            $comic_select .= '<option value="' . $comics[$c] . '"';
            if ($comics[$c] == $index) {
                $comic_select .= ' selected="selected"';
                if ($c > 0) {
                    $prev_comic = $c - 1;
                }
                if ($c < count($comics) - 1) {
                    $next_comic = $c + 1;
                }
            }
            $comic_select .= '>' . $klutz->getProperty($comics[$c], 'name') .
                '</option>';
        }
        $comic_select = '<select name="index" onchange="this.form.submit()">'
            . $comic_select . '</select>';
        $comic_url = Horde_Util::addParameter(Horde::selfUrl(false, false), array('actionID' => $actionID, 'date' => $date));
        if ($prev_comic) {
            $comic_select = Horde::link(Horde_Util::addParameter($comic_url, 'index', $comics[$prev_comic])) . Horde::img('nav/left.png') . '</a> ' . $comic_select;
        } else {
            $comic_select = Horde::img('nav/left-grey.png') . ' ' . $comic_select;
        }
        if ($next_comic) {
            $comic_select .= ' ' . Horde::link(Horde_Util::addParameter($comic_url, 'index', $comics[$next_comic])) . Horde::img('nav/right.png') . '</a>';
        } else {
            $comic_select .= ' ' . Horde::img('nav/right-grey.png');
        }
    }

    // Display the navbar.
    $url = Horde_Util::addParameter($url, 'index', $index);
    if (count($klutz_driver->listDates($prev_month))) {
        $prev_month_url = Horde_Util::addParameter($url, 'date', $prev_month);
    }
    if (count($klutz_driver->listDates($next_month))) {
        $next_month_url = Horde_Util::addParameter($url, 'date', $next_month);
    }

    $page_output->header(array(
        'title' => $title
    ));
    echo Horde::menu();
    require KLUTZ_TEMPLATES . '/comics/nav_bar.inc';
    if (!empty($imageApp)) {
        $page_output->addScriptFile('popup.js', 'horde');
    }

    $name = $klutz->getProperty($index, 'name');
    $author = $klutz->getProperty($index, 'author');
    $homepage = $klutz->getProperty($index, 'homepage');
    $title = sprintf(_("%s by %s"), $name, $author);
    foreach (array_reverse($klutz_driver->listDates($date)) as $date) {
        if ($klutz_driver->imageExists($index, $date)) {
            $size = $klutz_driver->imageSize($index, $date);
            $url = Horde_Util::addParameter(Horde::selfUrl(false, false), array('actionID' => 'image',
                                                                          'date' => $date,

                                                                          'index' => $index));
            // We have a comic, build a link to save to a gallery.
            if (!empty($imageApp)) {
                $popupUrl = Horde_Util::addParameter('savecomic.php', array('date' => $date,
                                                                      'index' => $index));
                $saveImgLink = Horde::link('#', _("Save Comic to Gallery"), null,
                                           null, 'popup(\'' . $popupUrl . '\', 450, 290); return false;') .
                                          '<img src="' . $registry->get('icon', $imageApp) . '" alt="' . _("Save Comic to Gallery") . '" /></a>';

            }

            require KLUTZ_TEMPLATES . '/comics/comic.inc';
        } elseif ($klutz->getProperty($index, 'days') == 'random' ||
                  !in_array(Horde_String::lower(date('D', $date)), $klutz->getProperty($index, 'days'))) {
            continue;
        } else {
            require KLUTZ_TEMPLATES . '/comics/missing.inc';
        }
    }
    require KLUTZ_TEMPLATES . '/comics/nav_bar.inc';
    break;
}

$page_output->footer();
