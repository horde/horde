<?php
/**
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Martin Blumenthal <tinu@humbapa.ch>
 */

@define('SKOLI_BASE', dirname(__FILE__));
require_once SKOLI_BASE . '/lib/base.php';

$title = _("My Classes");

/* Get and set Variables */
$vars = Horde_Variables::getDefaultVariables();

/* Get the current action ID. */
$actionID = Horde_Util::getFormData('actionID');

/* Sort out the sorting values */
if (($sortby_class = Horde_Util::getFormData('sortby_class')) !== null) {
    if ($sortby_class == $prefs->getValue('sortby_class')) {
        $prefs->setValue('sortdir_class', !$prefs->getValue('sortdir_class'));
    } else {
        $prefs->setValue('sortdir_class', SKOLI_SORT_ASCEND);
    }
    $prefs->setValue('sortby_class', $sortby_class);
    if ($sortby_class == 'name') {
        if ($sortby_class == $prefs->getValue('sortby_student')) {
            $prefs->setValue('sortdir_student', !$prefs->getValue('sortdir_student'));
        } else {
            $prefs->setValue('sortdir_student', SKOLI_SORT_ASCEND);
        }
        $prefs->setValue('sortby_student', $sortby_class);
    }
}
if (($sortby_student = Horde_Util::getFormData('sortby_student')) !== null) {
    $prefs->setValue('sortby_student', $sortby_student);
    if ($sortby_student == $prefs->getValue('sortby_student')) {
        $prefs->setValue('sortdir_student', !$prefs->getValue('sortdir_student'));
    } else {
        $prefs->setValue('sortdir_student', SKOLI_SORT_ASCEND);
    }
}

/* Check if we have access to an application who provides contacts/getContact */
$app = $registry->hasMethod('contacts/getContact');
if ($app == false || $registry->get('status', $app) == 'inactive' || !$registry->hasPermission($app, Horde_Perms::SHOW)) {
    $notification->push(_("Skoli needs an applications who provides contacts (e.g. turba)."), 'horde.warning');
}

/* Redirect to create a new class if we don't have access to any class */
if (count(Skoli::listClasses()) == 0 && $GLOBALS['registry']->getAuth()) {
    $notification->push(_("Please create a new Class first."), 'horde.message');
    Horde::url('classes/create.php', true)->redirect();
}

switch ($actionID) {
case 'search_classes':
    /* Get the search parameters. */
    $search_pattern = Horde_Util::getFormData('search_pattern');

    /* Get the full, sorted student list for all classes. */
    $list = Skoli::listStudents(null,
                                $prefs->getValue('sortby_student'),
                                $prefs->getValue('sortdir_student'),
                                $prefs->getValue('sortby_class'),
                                $prefs->getValue('sortdir_class'));

    if (!empty($search_pattern)) {
        $pattern = '/' . preg_quote($search_pattern, '/') . '/i';
        $search_results = array();
        foreach ($list as $class) {
            $search_results_students = array();
            if (($search_name && preg_match($pattern, $task->name)) ||
                ($search_desc && preg_match($pattern, $task->desc)) ||
                ($search_category && preg_match($pattern, $task->category))) {
                $search_results->add($task);
            }
        }

        /* Reassign $list to the search result. */
        $list = $search_results;
        $title = sprintf(_("Search: Results for \"%s\""), $search_pattern);
    }
    break;

default:
    /* Get the full, sorted list for all classes. */
    $list = Skoli::listStudents(null,
                                $prefs->getValue('sortby_student'),
                                $prefs->getValue('sortdir_student'),
                                $prefs->getValue('sortby_class'),
                                $prefs->getValue('sortdir_class'));
    break;
}

Horde::addScriptFile('tooltips.js', 'horde');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('quickfinder.js', 'horde');

require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
echo '<div id="page">';
require SKOLI_TEMPLATES . '/list/header.inc';

if (count($list) > 0) {
    $sortby_class = $prefs->getValue('sortby_class');
    $sortdir_class = $prefs->getValue('sortdir_class');
    $sortby_student = $prefs->getValue('sortby_student');
    $sortdir_student = $prefs->getValue('sortdir_student');
    $dateFormat = $prefs->getValue('date_format');
    $class_columns = @unserialize($prefs->getValue('class_columns'));
    $show_students = $prefs->getValue('show_students');
    $student_columns = $show_students ? @unserialize($prefs->getValue('student_columns')) : array();
    $dynamic_sort = true;

    $baseurl = 'list.php';
    if ($actionID == 'search_classes') {
        $baseurl = Horde_Util::addParameter(
            $baseurl,
            array('actionID' => 'search_classes',
                  'search_pattern' => $search_pattern));
    }

    require SKOLI_TEMPLATES . '/list/headers.inc';

    foreach ($list as $class) {
        $dynamic_sort &= !$show_students;
        $style = 'linedRow';
        require SKOLI_TEMPLATES . '/list/classes.inc';

        if ($show_students) {
            $treedir = Horde_Themes::img(null, 'horde');
            $counter = 0;
            foreach ($class['_students'] as $student) {
                if (++$counter < count($class['_students'])) {
                    $treeIcon = Horde::img(empty($GLOBALS['registry']->nlsconfig['rtl'][$GLOBALS['language']]) ? 'tree/join.png' : 'tree/rev-join.png', '+', '', $treedir);
                } else {
                    $treeIcon = Horde::img(empty($GLOBALS['registry']->nlsconfig['rtl'][$GLOBALS['language']]) ? 'tree/joinbottom.png' : 'tree/rev-joinbottom.png', '\\', '', $treedir);
                }
                require SKOLI_TEMPLATES . '/list/students.inc';
            }
        }
    }

    require SKOLI_TEMPLATES . '/list/footers.inc';

    if ($dynamic_sort) {
        Horde::addScriptFile('tables.js', 'horde');
    }
} else {
    require SKOLI_TEMPLATES . '/list/empty.inc';
}

require SKOLI_TEMPLATES . '/panel.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
