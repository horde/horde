<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

/**
 * Output text only if this is the web interface.
 *
 * @param string $text  The text to print.
 */
function webPrint($text, $flush = false)
{
    if (!$GLOBALS['cli']->runningFromCLI() && empty($GLOBALS['redirect'])
        && $GLOBALS['action'] != 'day') {
        echo $text;

        if ($flush) {
            if (ob_get_level() !== false) {
                ob_flush();
            }
            flush();
        }
    }
}

$no_compress = true;
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('klutz');

$cli = $injector->getInstance('Horde_Cli');

// Check for a Klutz administrator.
if (!$cli->runningFromCli() && !$registry->isAdmin(array('permission' => 'klutz:admin'))) {
    exit('forbidden');
}

// Don't do anything if the backend is none.
if ($conf['storage']['driver'] == 'none') {
    if ($cli->runningFromCLI()) {
        fwrite(STDERR, _("You must define a storage backend to use the administrative interface."));
        exit(1);
    } else {
        $notification->push(_("You must define a storage backend to use the administrative interface."), 'horde.error');
        header('Location: ' . Horde::url('comics.php'));
        exit;
    }
}

// Should we redirect when we're done?
$redirect = Horde_Util::getFormData('redirect');
$action = Horde_Util::getFormData('action');

// No more notifications after this point, so close the session so we
// don't lock other pages - if redirecting, we are only fetching one comic
// and would like a notification on success/failure.
if (empty($redirect)) {
    session_write_close();
}

// Proceed once we're sure we're authorized.
// $mode is either set from the cli.backend.* scripts as an array of modes
// or passed in as a single mode from the form variable.
$mode = Horde_Util::nonInputVar('mode', Horde_Util::getFormData('mode', array('menu')));
if (!is_array($mode)) {
    $mode = array($mode);
}

$time = mktime(0, 0, 0);
$t = getdate($time);
if (!empty($conf['backend']['daystokeep'])) {
    $daystokeep = $conf['backend']['daystokeep'];
    $oldest = mktime(0, 0, 0, $t['mon'], $t['mday'] - $daystokeep, $t['year']);
} else {
    $daystokeep = 90;
    $oldest = null;
}
$expired = mktime(0, 0, 0, $t['mon'], $t['mday'] - ($daystokeep + 1), $t['year']);

if (in_array('sums', $mode)) {
    $klutz_driver->rebuildSums();
    if (!in_array('menu', $mode)) {
        webPrint(_("The unique identifiers table has been rebuilt."));
    }
}

if (count($mode) == 0 || in_array('menu', $mode)) {
    $fetch_date_select = '<select name="date">' .
        '<option value="all">' . _("All Dates") . '</option>';
    foreach (array_reverse(Klutz_Driver::listDates($time, $oldest)) as $date) {
        if ($date == $time) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        $fetch_date_select .= '<option value="' . $date . '"' . $selected . '">' .
            strftime('%B %d, %Y', $date) . '</option>';
    }
    $fetch_date_select .= '</select>';

    $delete_date_select = '<select name="date">';
    foreach ($klutz_driver->listDates($time, 0, $time) as $date) {
        if ($date == $oldest) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }
        $delete_date_select .= '<option value="' . $date . '"' . $selected . '">' .
            strftime("%B %d, %Y", $date) . '</option>';
    }
    $delete_date_select .= '</select>';

    $comic_select = '<select name="index"><option value="all" selected="selected">' .
        _("All Comics") . '</option>';
    foreach ($klutz->listEnabled() as $index) {
        $comic_select .= "<option value=\"$index\">" .
            $klutz->getProperty($index, 'name') .
            '</option>';
    }
    $comic_select .= '</select>';

    $sums_url = Horde::url('backend.php')->add('mode[]', 'sums')->add('mode[]', 'menu');

    $page_output->header(array(
        'title' => _("Comics Update")
    ));
    require KLUTZ_TEMPLATES . '/backend.html.php';
    $page_output->footer();
    exit;
}

/* Make it at least look prettier if we are running from web */
if (!$cli->runningFromCLI() && empty($redirect)) {
    $page_output->header(array(
        'title' => _("Comics Update")
    ));
}

if (in_array('fetch', $mode)) {
    if (empty($date)) {
        $date = Horde_Util::getFormData('date', mktime(0, 0, 0));
    }
    if (empty($index)) {
        $index = Horde_Util::getFormData('index', 'all');
    }
    if (empty($overwrite)) {
        $overwrite = Horde_Util::getFormData('overwrite', 'false');
    }
    if (empty($nounique)) {
        $nounique = Horde_Util::getFormData('nounique', 'false');
    }

    if (Horde_String::lower($overwrite) == 'true') {
        $overwrite = true;
    } else {
        $overwrite = false;
    }
    if (Horde_String::lower($nounique) == 'true') {
        $nounique = true;
    } else {
        $nounique = false;
    }

    if (Horde_String::lower($date) == 'all') {
        $dates = array_reverse(Klutz_Driver::listDates($time, $oldest));
    } else {
        // make sure the time for $date is midnight
        $d = getdate($date);
        $date = mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);
        $dates = array($date);
    }

    foreach ($dates as $date) {
        // is $date today?
        $today = false;
        if ($date != 'all' && $date == mktime(0, 0, 0)) {
            $today = true;
        }

        if (Horde_String::lower($index) == 'all') {
            $comics = $klutz->listEnabled(null, $date);
        } else {
            $comics = $klutz->listEnabled(array($index), $date);
        }

        foreach ($comics as $comic) {
            // If this comic isn't available all days, nohistory gets
            // ugly. SOMEONE PLEASE MAKE IT BETTER ;)
            $name = $klutz->getProperty($comic, 'name');
            $days = $klutz->getProperty($comic, 'days');
            if ($days != 'random' && count($days) < 7) {
                $day = mktime(0, 0, 0);
                while (!in_array(Horde_String::lower(date('D', $day)), $days)) {
                    $d = getdate($day);
                    $day = mktime(0, 0, 0, $d['mon'], $d['mday'] - 1, $d['year']);
                }
                if ($day == $date) {
                    $today = true;
                }
            }

            if (!$today && $klutz->getProperty($comic, 'nohistory')) {
                webPrint(sprintf(_("Skipping %s for %s - no historical fetching"),
                                 $name, strftime("%B %d, %Y", $date)));
            } elseif ($overwrite || !$klutz_driver->imageExists($comic, $date)) {
                webPrint(sprintf(_("Fetching %s for date %s..."),
                                 $name, strftime('%B %d, %Y', $date)), true);

                $c = $klutz->comicObject($comic);
                $image = $c->fetchImage($date);
                if (!is_object($image)) {
                    if (empty($redirect)) {
                        webPrint(_("The image doesn't exist or can't be retrieved"));
                    } else {
                        $GLOBALS['notification']->push(_("The image doesn't exist or can't be retrieved"));
                    }
                } elseif (!$nounique && (method_exists($klutz_driver, 'isUnique') &&
                          !$klutz_driver->isUnique($image))) {
                    webPrint(sprintf(_("This image appears to be a repeat (%s)"), md5($image->data)));
                } elseif ($klutz_driver->storeImage($comic, $image, $date)) {
                    if (empty($redirect)) {
                        webPrint(_("Done"));
                    } else {
                        $GLOBALS['notification']->push(sprintf(_("Fetching %s for date %s...Done"),
                                                       $name, strftime('%B %d, %Y', $date)));
                    }
                } else {
                    webPrint(_("Error storing the image"));
                }
            } else {
                webPrint(sprintf(_("Skipping %s for %s"),
                                 $name, strftime('%B %d, %Y', $date)));
            }
            webPrint("<br />\n");
        }
    }
}

if (in_array('delete', $mode)) {
    // Note: We don't allow overriding of $date from backend.php because it
    // would conflict with the $date used for fetching so we'd delete all
    // comics if fetch and delete were both requested in one file.
    $date = Horde_Util::getFormData('date', $expired);
    $index = Horde_Util::getFormData('index', 'all');
    $timeframe = Horde_Util::getFormData('timeframe', 'older');

    // make sure the time for $date is midnight
    $d = getdate($date);
    $date = mktime(0, 0, 0, $d['mon'], $d['mday'], $d['year']);

    $success = _("Successfully removed %s for %s<br />\n");
    $notfound = _("Could not find %s for %s<br />\n");
    $error = _("An error occurred removing %s for %s<br />\n");

    // Figure out what dates we need to work on
    if ($timeframe == 'date') {
        $dates = array($date);
    } elseif ($timeframe == 'older') {
        $dates = $klutz_driver->listDates($date, 0, $date);
    } elseif ($timeframe == 'newer') {
        $dates = $klutz_driver->listDates($date, $date, mktime(0, 0, 0));
    } else {
        $dates = array();
    }

    if ($index == 'all') {
        if (count($dates) == 0) {
            webPrint(sprintf($notfound, _("any comics"), _("any dates")));
        }
        foreach ($dates as $date) {
            if ($klutz_driver->removeDate($date)) {
                webPrint(sprintf($success, _("all comics"), strftime('%B %d, %Y', $date)));
            } else {
                webPrint(sprintf($error, _("all comics"), strftime('%B %d, %Y', $date)));
            }
        }
    } else {
        $name = $klutz->getProperty($index, 'name');
        $days = $klutz->getProperty($index, 'days');

        foreach ($dates as $date) {
            if ($days == 'random' || in_array(Horde_String::lower(date('D', $date)), $days)) {
                if (!$klutz_driver->imageExists($index, $date)) {
                    webPrint(sprintf($notfound, $index, strftime('%B %d, %Y', $date)));
                } else {
                    if ($klutz_driver->removeImage($index, $date)) {
                        webPrint(sprintf($success, $name, strftime('%B %d, %Y', $date)));
                    } else {
                        webPrint(sprintf($error, $name, strftime('%B %d, %Y', $date)));
                    }
                }
            }
        }
    }
}

// Save the updated sums if necessary.
if (method_exists($klutz_driver, 'saveSums')) {
    $klutz_driver->saveSums();
}

// Redirect?
if (!empty($redirect)) {
    header('Location: ' . Horde::url($redirect)->add(array(
        'actionID' => Horde_Util::getFormData('action'),
        'date' => $date,
        'index' => $index))->setRaw(true));
}

if (!$cli->runningFromCLI()) {
    $page_output->footer();
}
