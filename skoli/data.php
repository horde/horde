<?php
/**
 * Copyright 2001-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Martin Blumenthal <tinu@humbapa.ch>
 */

require_once dirname(__FILE__) . '/lib/base.php';

if (!$conf['menu']['export']) {
    Horde::applicationUrl('list.php', true)->redirect();
}

$classes = Skoli::listClasses();

/* If there are no valid classes, abort. */
if (count($classes) == 0) {
    $notification->push(_("No classes are currently available. Export is disabled."), 'horde.error');
    require SKOLI_TEMPLATES . '/common-header.inc';
    require SKOLI_TEMPLATES . '/menu.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

$class_options = array();
foreach ($classes as $key=>$class) {
    $class_options[] = '<option value="' . htmlspecialchars($key) . '"' . (Horde_Util::getFormData('class') == $key ? ' selected="selected"' : '') . '>' .
                       htmlspecialchars($class->get('name')) . "</option>\n";
}

$wholeclass_option = '<option value="all">' .
                     htmlspecialchars(_("Whole class")) . "</option>\n";
$student_options = array();
$student_options[] = $wholeclass_option;
if (Horde_Util::getFormData('class') != '') {
    $class = Horde_Util::getFormData('class');
} else {
    reset($classes);
    $class = key($classes);
}
$export_class = current(Skoli::listStudents($class, SKOLI_SORT_NAME, SKOLI_SORT_ASCEND));
foreach ($export_class['_students'] as $address) {
    $student_options[] = '<option value="' . htmlspecialchars($address['student_id']) . '">' .
                         htmlspecialchars($address[$conf['addresses']['name_field']]) . "</option>\n";
}

$actionID = Horde_Util::getFormData('actionID');

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    $data = array();
    $driver = &Skoli_Driver::singleton($class);
    if (Horde_Util::getFormData('student') == 'all') {
        /* Export whole class. */
        $subjects = $driver->getSubjects('mark');
        foreach ($export_class['_students'] as $student) {
           $row = array();
           $row[_("Class")] = $export_class['name'];
           $row[_("Firstname")] = $student['firstname'];
           $row[_("Lastname")] = $student['lastname'];

           /* Absences */
           $absences = Skoli::sumAbsences($class, $student['student_id']);
           $row[_("Excused absences")] = $absences[0];
           $row[_("Absences without valid excuse")] = $absences[1];

           /* Marks */
           foreach ($subjects as $subject) {
               $row[$subject] = Skoli::sumMarks($class, $student['student_id'], $subject);
           }

           /* Outcomes */
           $outcomes = Skoli::sumOutcomes($class, $student['student_id']);
           $row[_("Completed outcomes")] = $outcomes[0];
           $row[_("Open outcomes")] = $outcomes[1];

           $data[] = $row; 
        }
        /* Make sure that only columns with data are exportet. */
        if (count($data)) {
            foreach ($data[0] as $key=>$value) {
                $emptycolumn = true;
                foreach ($data as $row) {
                    if ($row[$key] !== '') {
                        $emptycolumn = false;
                        break;
                    }
                }
                if ($emptycolumn) {
                    foreach ($data as $rowkey=>$row) {
                        unset($data[$rowkey][$key]);
                    }
                }
            }
        }
    } else {
        /* Export all entries for the selected student. */
        $data[] = array(_("Marks"));
        $subjects = $driver->getSubjects('mark');
        foreach ($subjects as $subject) {
            $params = array(array('name' => 'subject', 'value' => $subject, 'strict' => 1));
            $marks = Skoli::listEntries($class, Horde_Util::getFormData('student'), 'mark', $params, SKOLI_SORT_DATE, SKOLI_SORT_DESCEND);
            foreach ($marks as $mark) {
                $data[] = array($subject, $mark['date'], $mark['title'], Skoli::convertNumber($mark['mark']), Skoli::convertNumber($mark['weight']));
            }
        }

        $data[] = array(_("Objectives"));
        $subjects = $driver->getSubjects('objective');
        foreach ($subjects as $subject) {
            $params = array(array('name' => 'subject', 'value' => $subject, 'strict' => 1));
            $objectives = Skoli::listEntries($class, Horde_Util::getFormData('student'), 'objective', $params, SKOLI_SORT_DATE, SKOLI_SORT_DESCEND);
            foreach ($objectives as $objective) {
                $data[] = array($subject, $objective['date'], $objective['category'], $objective['objective']);
            }
        }

        $data[] = array(_("Outcomes"));
        $outcomes = Skoli::listEntries($class, Horde_Util::getFormData('student'), 'outcome', null, SKOLI_SORT_DATE, SKOLI_SORT_DESCEND);
        foreach ($outcomes as $outcome) {
            $completed = isset($outcome['completed']) && $outcome['completed'] != '' ? _("Completed") : _("Open");
            $comment = isset($outcome['comment']) ? $outcome['comment'] : '';
            $data[] = array($outcome['date'], $outcome['outcome'], $completed, $comment);
        }

        $data[] = array(_("Absences"));
        $absences = Skoli::listEntries($class, Horde_Util::getFormData('student'), 'absence', null, SKOLI_SORT_DATE, SKOLI_SORT_DESCEND);
        foreach ($absences as $absence) {
            $excused = isset($absence['excused']) && $absence['excused'] != '' ? _("Excused") : _("Not excused");
            $comment = isset($absence['comment']) ? $absence['comment'] : '';
            $data[] = array($absence['date'], Skoli::convertNumber($absence['absence']), $excused, $comment);
        }

        /* Make sure that all rows have the same number of columns. */
        $maxcols = 0;
        for ($i=0; $i < count($data); $i++) {
            if (count($data[$i]) > $maxcols) {
                $maxcols = count($data[$i]);
            }
        }
        for ($i=0; $i < count($data); $i++) {
            for ($irow=0; $irow < $maxcols; $irow++) {
                if (!isset($data[$i][$irow])) {
                    $data[$i][$irow] = '';
                }
            }
        }
    }
    if (!count($data)) {
        $notification->push(_("There were no entries to export."), 'horde.message');
        break;
    }

    switch (Horde_Util::getFormData('exportID')) {
    case EXPORT_CSV:
        $injector->getInstance('Horde_Data')->getData('Csv')->exportFile(_("class.csv"), $data, (Horde_Util::getFormData('student') == 'all'));
        exit;

    case EXPORT_TSV:
        $injector->getInstance('Horde_Data')->getData('Tsv')->exportFile(_("class.tsv"), $data, (Horde_Util::getFormData('student') == 'all'));
        exit;

    }
    break;
}

$title = _("Export Classes");

Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';
require SKOLI_TEMPLATES . '/data/export.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
