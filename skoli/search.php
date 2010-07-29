<?php
/**
 * Copyright 2000-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Martin Blumenthal <tinu@humbapa.ch>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$classes = Skoli::listClasses();

/* If there are no valid classes, abort. */
if (count($classes) == 0) {
    $notification->push(_("No classes are currently available. Searching is disabled."), 'horde.error');
    require SKOLI_TEMPLATES . '/common-header.inc';
    require SKOLI_TEMPLATES . '/menu.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

$actionID = Horde_Util::getFormData('actionID');

if (!isset($_SESSION['skoli'])) {
    $_SESSION['skoli'] = array();
}

if (($classid = Horde_Util::getFormData('class')) !== null) {
    $_SESSION['skoli']['search_classid'] = $classid;
} else if (isset($_SESSION['skoli']['search_classid'])) {
    $classid = $_SESSION['skoli']['search_classid'];
}
if (($studentid = Horde_Util::getFormData('student')) !== null) {
    $_SESSION['skoli']['search_studentid'] = $studentid;
} else if (isset($_SESSION['skoli']['search_studentid'])) {
    $studentid = $_SESSION['skoli']['search_studentid'];
}
if (($type = Horde_Util::getFormData('type')) !== null) {
    $_SESSION['skoli']['search_type'] = $type;
} else if (isset($_SESSION['skoli']['search_type'])) {
    $type = $_SESSION['skoli']['search_type'];
}
if (($search = Horde_Util::getFormData('stext')) !== null) {
    $_SESSION['skoli']['search_stext'] = $search;
} else if (isset($_SESSION['skoli']['search_stext'])) {
    $search = $_SESSION['skoli']['search_stext'];
}

/* Sort out the sorting values */
$sortby = Horde_Util::getFormData('sortby');
$sortdir = Horde_Util::getFormData('sortdir');
if ($sortby === null) {
    $sortby = SKOLI_SORT_CLASS;
} else if ($sortby == Horde_Util::getFormData('sortby')) {
    $sortdir = !$sortdir;
}
if ($sortdir === null) {
    $sortdir = SKOLI_SORT_ASCEND;
}

$class_options = array();
if (count($classes) > 1) {
    $class_options[] = '<option value="all">' .
                       htmlspecialchars(_("All classes")) . "</option>\n";
}
foreach ($classes as $key=>$class) {
    $class_options[] = '<option value="' . htmlspecialchars($key) . '"' . ($classid == $key ? ' selected="selected"' : '') . '>' .
                       htmlspecialchars($class->get('name')) . "</option>\n";
}

$student_options = array();
$student_options[] = '<option value="all">' .
                     htmlspecialchars(_("All students")) . "</option>\n";
if ($classid == '' || $classid == 'all') {
    $studentslist = Skoli::listStudents(null, SKOLI_SORT_NAME, SKOLI_SORT_ASCEND);
    $students = array();
    foreach ($studentslist as $val) {
        $students = array_merge($students, $val['_students']);
    }
} else {
    $studentslist = current(Skoli::listStudents($classid, SKOLI_SORT_NAME, SKOLI_SORT_ASCEND));
    $students = $studentslist['_students'];
}
$foundstudent = false;
foreach ($students as $address) {
    if ($studentid == $address['student_id']) {
        $foundstudent = true;
    }
    $student_options[] = '<option value="' . htmlspecialchars($address['student_id']) . '"' . ($studentid == $address['student_id'] ? ' selected="selected"' : '') . '">' .
                         htmlspecialchars($address[$conf['addresses']['name_field']]) . "</option>\n";
}
if (!$foundstudent && $studentid != 'all') {
    $actionID = '';
    $studentid = '';
    $_SESSION['skoli']['search_studentid'] = $studentid;
}

$type_options = array();
$type_options[] = '<option value="all">' .
                  htmlspecialchars(_("All Types")) . "</option>\n";
if ($conf['objects']['allow_marks']) {
    $type_options[] = '<option value="mark"' . ($type == 'mark' ? ' selected="selected"' : '') . '>' .
                      htmlspecialchars(_("Marks")) . "</option>\n";
}
if ($conf['objects']['allow_objectives']) {
    $type_options[] = '<option value="objective"' . ($type == 'objective' ? ' selected="selected"' : '') . '>' .
                      htmlspecialchars(_("Objectives")) . "</option>\n";
}
if ($conf['objects']['allow_outcomes']) {
    $type_options[] = '<option value="outcome"' . ($type == 'outcome' ? ' selected="selected"' : '') . '>' .
                      htmlspecialchars(_("Outcomes")) . "</option>\n";
}
if ($conf['objects']['allow_absences']) {
    $type_options[] = '<option value="absence"' . ($type == 'absence' ? ' selected="selected"' : '') . '>' .
                      htmlspecialchars(_("Absences")) . "</option>\n";
}

Horde::addInlineScript(array(
    '$("stext").focus()'
), 'dom');

$title = _("Search");

Horde::addScriptFile('quickfinder.js', 'horde');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
reset($classes);
require SKOLI_TEMPLATES . '/search/criteria.inc';

if ($actionID == 'search') {
    $params = array($search);
    $list = Skoli::listEntries($classid == 'all' ? null : $classid, $studentid == 'all' ? null : $studentid, $type == 'all' ? null : $type, $params, $sortby, $sortdir);

    $dynamic_sort = false;
    $params = array('actionID' => 'search');
    $baseurl = Horde_Util::addParameter('search.php', $params);
    echo '<div id="page">';
    require SKOLI_TEMPLATES . '/search/header.inc';
    if (count($list) > 0) {
        require SKOLI_TEMPLATES . '/search/headers.inc';
        foreach ($list as $entry) {
            $style = 'linedRow';
            $details = '';

            switch ($entry['type']) {
            case 'mark':
                $details = $entry['subject'] . ': ' . $entry['mark'] .
                           ($classes[$entry['classid']]->get('marks') == 'percent' ? '%' : '') .
                           ' (' . $entry['weight'] . '), ' . $entry['title'];
                break;

            case 'objective':
                $details = $entry['category'] . ' (' . $entry['subject'] . '): ' . $entry['objective'];
                break;

            case 'outcome':
                $details = $entry['outcome'] . ': ' .
                           (isset($entry['completed']) && $entry['completed'] != '' ? _("Completed") : _("Open")) .
                           (isset($entry['comment']) && $entry['comment'] != '' ? ', ' . $entry['comment'] : '');
                break;

            case 'absence':
                $details = (isset($entry['excused']) && $entry['excused'] != '' ? _("Excused") : _("Not excused")) .
                           ': ' . $entry['absence'] .
                           (isset($entry['comment']) && $entry['comment'] != '' ? ', ' . $entry['comment'] : '');
                break;
            }
            $detailswrapped = Horde_String::wordwrap($details, $prefs->getValue('entry_details_wrap'), '<br />', true);
            $entry['details'] = current(explode('<br />', $detailswrapped));
            require SKOLI_TEMPLATES . '/search/entries.inc';
        }

        require SKOLI_TEMPLATES . '/search/footers.inc';

        if ($dynamic_sort) {
            Horde::addScriptFile('tables.js', 'horde');
        }
    } else {
        require SKOLI_TEMPLATES . '/search/empty.inc';
    }
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
