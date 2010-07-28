#!/usr/bin/php -q
<?php
/**
 * A script to update users preferences to combine their categories
 * and category colors from Genie, Kronolith, Mnemo, and Nag into the
 * new Horde-wide preferences. Expects to be given a list of users on
 * STDIN, one username per line, to convert. Usernames need to match
 * the values stored in the preferences backend.
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none', 'cli' => true));

$cManager = new Horde_Prefs_CategoryManager();
$apps = $registry->listApps(array('hidden', 'notoolbar', 'active', 'admin'));

// Read in the list of usernames on STDIN.
$users = array();
while (!feof(STDIN)) {
    $line = fgets(STDIN);
    $line = trim($line);
    if (!empty($line)) {
        $users[] = $line;
    }
}

// Loop through users and convert prefs for Genie, Mnemo, Nag, and
// Kronolith.
foreach ($users as $user) {
    echo 'Migrating prefs for ' . $cli->bold($user);

    // Set $user as the current user.
    $registry->setAuth($user, array());

    // Fetch current categories and colors.
    $colors = $cManager->colors();

    // Genie.
    if (in_array('genie', $apps)) {
        echo ' . genie';
        try {
            $registry->pushApp('genie', array('check_perms' => false));
            $g_categories = listCategories('wish_categories');
            foreach ($g_categories as $category) {
                $cManager->add($category);
            }
        } catch (Horde_Exception $e) {}
    }

    // Mnemo.
    if (in_array('mnemo', $apps)) {
        echo ' . mnemo';
        try {
            $registry->pushApp('mnemo', array('check_perms' => false));
            $m_categories = listCategories('memo_categories');
            $m_colors = listColors('memo_colors');
            foreach ($m_categories as $key => $category) {
                if (isset($m_colors[$key])) {
                    $colors[$category] = $m_colors[$key];
                }
                $cManager->add($category);
            }
        } catch (Horde_Exception $e) {}
    }

    // Nag.
    if (in_array('nag', $apps)) {
        echo ' . nag';
        try {
            $registry->pushApp('nag', array('check_perms' => false));
            $n_categories = listCategories('task_categories');
            foreach ($n_categories as $category) {
                $cManager->add($category);
            }
        } catch (Horde_Exception $e) {}
    }

    // Kronolith.
    if (in_array('kronolith', $apps)) {
        echo ' . kronolith';
        try {
            $registry->pushApp('kronolith', array('check_perms' => false));
            $k_categories = listCategories('event_categories');
            if (count($k_categories)) var_dump($k_categories);
            $k_colors = listColors('event_colors');
            foreach ($k_categories as $key => $category) {
                if (isset($k_colors[$key])) {
                    $colors[$category] = $k_colors[$key];
                }
                $cManager->add($category);
            }
        } catch (Horde_Exception $e) {}
    }

    $cManager->setColors($colors);
    $prefs->store();
    $cli->writeln();
}

$cli->writeln();
$cli->writeln($cli->green('DONE'));
exit;

function listCategories($prefname)
{
    global $prefs;

    $string = $prefs->getValue($prefname);
    if (empty($string)) {
        return array();
    }

    $cats = explode('|', $string);
    foreach ($cats as $cat) {
        list($key, $val) = explode(':', $cat);
        $categories[$key] = $val;
    }

    return $categories;
}

function listColors($prefname)
{
    global $prefs;

    $string = $prefs->getValue($prefname);
    if (empty($string)) {
        return array();
    }
    $cols = explode('|', $string);
    $colors = array();
    foreach ($cols as $col) {
        list($key, $val) = explode(':', $col);
        $colors[$key] = $val;
    }

    return $colors;
}
