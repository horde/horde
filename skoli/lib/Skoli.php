<?php
/**
 * Sort by student or class name.
 */
define('SKOLI_SORT_NAME', 'name');

/**
 * Sort by last entry date.
 */
define('SKOLI_SORT_LASTENTRY', 'lastentry');

/**
 * Sort by mark average.
 */
define('SKOLI_SORT_SUMMARKS', 'summarks');

/**
 * Sort by absences.
 */
define('SKOLI_SORT_SUMABSENCES', 'sumabsences');

/**
 * Sort by semester start date.
 */
define('SKOLI_SORT_SEMESTERSTART', 'semesterstart');

/**
 * Sort by semester end date.
 */
define('SKOLI_SORT_SEMESTEREND', 'semesterend');

/**
 * Sort by grade.
 */
define('SKOLI_SORT_GRADE', 'grade');

/**
 * Sort by semester.
 */
define('SKOLI_SORT_SEMESTER', 'semester');

/**
 * Sort by location.
 */
define('SKOLI_SORT_LOCATION', 'location');

/**
 * Sort by category.
 */
define('SKOLI_SORT_CATEGORY', 'category');

/**
 * Sort by entry class name.
 */
define('SKOLI_SORT_CLASS', 'class');

/**
 * Sort by entry student name.
 */
define('SKOLI_SORT_STUDENT', 'student');

/**
 * Sort by entry date.
 */
define('SKOLI_SORT_DATE', 'date');

/**
 * Sort by entry type.
 */
define('SKOLI_SORT_TYPE', 'type');

/**
 * Sort in ascending order.
 */
define('SKOLI_SORT_ASCEND', 0);

/**
 * Sort in descending order.
 */
define('SKOLI_SORT_DESCEND', 1);

/**
 * Skoli Base Class.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli {

    /**
     * Initial app setup code.
     */
    function initialize()
    {
        // Update the preference for what classes to display. If the user
        // doesn't have any selected class then do nothing.
        $GLOBALS['display_classes'] = @unserialize($GLOBALS['prefs']->getValue('display_classes'));
        if (!$GLOBALS['display_classes']) {
            $GLOBALS['display_classes'] = array();
        }
        if (($classId = Horde_Util::getFormData('display_class')) !== null) {
            if (is_array($classId)) {
                $GLOBALS['display_classes'] = $classId;
            } else {
                if (in_array($classId, $GLOBALS['display_classes'])) {
                    $key = array_search($classId, $GLOBALS['display_classes']);
                    unset($GLOBALS['display_classes'][$key]);
                } else {
                    $GLOBALS['display_classes'][] = $classId;
                }
            }
            $GLOBALS['prefs']->setValue('show_students', Horde_Util::getFormData('show_students') ? 1 : 0);
        }

        $GLOBALS['prefs']->setValue('display_classes', serialize($GLOBALS['display_classes']));
    }

    /**
     * Returns all classes a user has access to, according to several
     * parameters/permission levels.
     *
     * @param boolean $owneronly   Only return classes that this user owns?
     *                             Defaults to false.
     * @param integer $permission  The permission to filter classes by.
     *
     * @return array  The class list.
     */
    function listClasses($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        if ($owneronly && !$GLOBALS['registry']->getAuth()) {
            return array();
        }

        $classes = $GLOBALS['skoli_shares']->listShares($GLOBALS['registry']->getAuth(), $permission, $owneronly ? $GLOBALS['registry']->getAuth() : null, 0, 0, 'name');
        if (is_a($classes, 'PEAR_Error')) {
            Horde::logMessage($classes, 'ERR');
            return array();
        }

        // Check if we have access to the attached addressbook.
        $addressbooks = $GLOBALS['registry']->call('contacts/sources');
        foreach ($classes as $key=>$val) {
            if (!isset($addressbooks[$val->get('address_book')])) {
                unset($classes[$key]);
            }
        }

        return $classes;
    }

    /**
     * Retrieves the current user's student list from storage.
     *
     * This function will also sort the resulting list, if requested.
     *
     * @param array $classes            An array of classes to display, a
     *                                  single classname or null/empty to
     *                                  display classes $GLOBALS['display_classes'].
     * @param string $sortby_student    The field by which to sort
     *                                  (SKOLI_SORT_PRIORITY, SKOLI_SORT_NAME
     *                                  SKOLI_SORT_DUE, SKOLI_SORT_COMPLETION).
     * @param integer $sortdir_student  The direction by which to sort
     *                                  (SKOLI_SORT_ASCEND, SKOLI_SORT_DESCEND).
     * @param string $sortby_class      The field by which to sort
     *                                  (SKOLI_SORT_PRIORITY, SKOLI_SORT_NAME
     *                                  SKOLI_SORT_DUE, SKOLI_SORT_COMPLETION).
     * @param integer $sortdir_class    The direction by which to sort
     *                                  (SKOLI_SORT_ASCEND, SKOLI_SORT_DESCEND).
     *
     * @return array  A list of the requested classes with students.
     */
    function listStudents($classes = null,
                          $sortby_student = null,
                          $sortdir_student = null,
                          $sortby_class = null,
                          $sortdir_class = null)
    {
        global $prefs, $registry;

        if (is_null($classes)) {
            $classes = $GLOBALS['display_classes'];
        } else if (!is_array($classes)) {
            $classes = array($classes);
        }
        if (is_null($sortby_student)) {
            $sortby_student = $prefs->getValue('sortby_student');
        }
        if (is_null($sortdir_student)) {
            $sortdir_student = $prefs->getValue('sortdir_student');
        }
        if (is_null($sortby_class)) {
            $sortby_class = $prefs->getValue('sortby_class');
        }
        if (is_null($sortdir_class)) {
            $sortdir_class = $prefs->getValue('sortdir_class');
        }

        $list = array();
        $i = 0;
        $addressbooks = $registry->call('contacts/sources');
        foreach ($classes as $class) {
            /* Get all data about the shared class */
            $share = $GLOBALS['skoli_shares']->getShare($class);

            /* Check permissions */
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ) || !isset($addressbooks[$share->get('address_book')])) {
                continue;
            }

            $list[$i] = $share->datatreeObject->data;
            $list[$i]['_id'] = $class;
            $list[$i]['_edit'] = $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);

            /* Add all students to the list */
            $driver = &Skoli_Driver::singleton($class);
            $list[$i]['_students'] = $driver->getStudents();
            $student_columns = @unserialize($prefs->getValue('student_columns'));
            foreach ($list[$i]['_students'] as $key=>$student) {
                $studentdetails = Skoli::getStudent($list[$i]['address_book'], $student['student_id']);
                if (count($studentdetails) > 0) {
                    $list[$i]['_students'][$key] += $studentdetails;
                    if (in_array('lastentry', $student_columns)) {
                        $list[$i]['_students'][$key]['_lastentry'] = $driver->lastEntry($student['student_id']);
                    }
                    if (in_array('summarks', $student_columns)) {
                        $list[$i]['_students'][$key]['_summarks'] = Skoli::sumMarks($class, $student['student_id']);
                    }
                    if (in_array('sumabsences', $student_columns)) {
                        $list[$i]['_students'][$key]['_sumabsences'] = Skoli::sumAbsences($class, $student['student_id']);
                    }
                } else {
                    unset($list[$i]['_students'][$key]);
                }
            }
            $i++;
        }

        /* Sort the array if we have a sort function defined for this
         * field. */
        $prefix = ($sortdir_class == SKOLI_SORT_DESCEND) ? '_rsort' : '_sort';
        usort($list, array('Skoli', $prefix . '_class_' . $sortby_class));
        $prefix = ($sortdir_student == SKOLI_SORT_DESCEND) ? '_rsort' : '_sort';
        for ($i = 0; $i < count($list); $i++) {
            usort($list[$i]['_students'], array('Skoli', $prefix . '_student_' . $sortby_student));
        }

        return $list;
    }

    /**
     * Retrieves all data about a student.
     *
     * @param string $addressbook  The addressbook.
     *
     * @param string $id           An ID from the student contact.
     *
     * @return array  A list with the data from the requested student.
     */
    function getStudent($addressbook, $id)
    {
        global $registry;

        $student = array();
        $apiargs = array(
            'source' => $addressbook,
            'objectId' => $id
        );
        try {
            $student = $registry->call('contacts/getContact', $apiargs);
        } catch (Horde_Exception $e) {
            $notification->push(sprintf(_("Couldn't create the contact list \"%s\"."), $this->_vars->get('contact_list')), 'horde.info');
        }
        return $student;
    }

    /**
     * Retrieves a sorted entry list from storage.
     *
     * @param string  $classid      The class ID.
     *
     * @param string  $studentid    The student ID.
     *
     * @param string  $type         The entry type to search in.
     *
     * @param array  $searchparams  Some additional search parameters.
     *
     * @param string $sortby        The field by which to sort
     *                              (SKOLI_SORT_CLASS, SKOLI_SORT_STUDENT
     *                              SKOLI_SORT_DATE, SKOLI_SORT_TYPE).
     * @param integer $sortdir      The direction by which to sort
     *                              (SKOLI_SORT_ASCEND, SKOLI_SORT_DESCEND).
     *
     * @return array  Sorted list with all entries.
     */
    function listEntries($classid = null,
                         $studentid = null,
                         $type = null,
                         $searchparams = array(),
                         $sortby = null,
                         $sortdir = null)
    {
        global $conf, $prefs, $registry;

        $dateFormat = $prefs->getValue('date_format');
        $entryTypes = array(
            'mark'      => _("Mark"),
            'objective' => _("Objective"),
            'outcome'   => _("Outcome"),
            'absence'   => _("Absence")
        );

        if (is_null($classid)) {
            $classes = Skoli::listClasses();
        } else {
            $share = $GLOBALS['skoli_shares']->getShare($classid);
            $classes = array($classid => $share);
        }

        if (is_null($sortby)) {
            $sortby = SKOLI_SORT_CLASS;
        }
        if (is_null($sortdir)) {
            $sortdir = SKOLI_SORT_ASCEND;
        }

        $entrylist = array();
        $i = 0;
        $addressbooks = $registry->call('contacts/sources');
        foreach ($classes as $class_id=>$share) {
            /* Check permissions */
            if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ) || !isset($addressbooks[$share->get('address_book')])) {
                continue;
            }

            $share_permissions_edit = $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
            $share_permissions_delete = $share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE);

            $driver = &Skoli_Driver::singleton($class_id);
            $entries = $driver->getEntries($studentid, $type, $searchparams);
            foreach ($entries as $student) {
                $studentdetails = Skoli::getStudent($share->get('address_book'), $student['student_id']);
                foreach ($student['_entries'] as $entry) {
                    $entrylist[$i] = $entry['_attributes'];
                    $entrylist[$i]['class'] = $share->get('name');
                    $entrylist[$i]['classid'] = $class_id;
                    $entrylist[$i]['student'] = $studentdetails[$conf['addresses']['name_field']];
                    $entrylist[$i]['date'] = strftime($dateFormat, $entry['object_time']);
                    $entrylist[$i]['timestamp'] = $entry['object_time'];
                    $entrylist[$i]['typename'] = $entryTypes[$entry['object_type']];
                    $entrylist[$i]['type'] = $entry['object_type'];
                    $entrylist[$i]['_edit'] = $share_permissions_edit;
                    $entrylist[$i]['_delete'] = $share_permissions_delete;
                    $entrylist[$i]['_id'] = $entry['object_id'];
                    $i++;
                }
            }
        }

        /* Sort the array if we have a sort function defined for this
         * field. */
        $prefix = ($sortdir == SKOLI_SORT_DESCEND) ? '_rsort' : '_sort';
        usort($entrylist, array('Skoli', $prefix . '_entry_' . $sortby));

        return $entrylist;
    }

    /**
     * Sum up all excused and not excused absences for a given student.
     *
     * @param string $classid   An ID from the class.
     *
     * @param string $studentid An ID from the student contact.
     *
     * @return array  A list with the requested data.
     */
    function sumAbsences($classid, $studentid)
    {
        $driver = &Skoli_Driver::singleton($classid);
        $entries = current($driver->getEntries($studentid, 'absence'));

        $excused = 0;
        $notexcused = 0;
        foreach($entries['_entries'] as $entry) {
            $entry['_attributes']['absence'] = Skoli::convertNumber($entry['_attributes']['absence']);
            if (empty($entry['_attributes']['excused'])) {
                $notexcused += $entry['_attributes']['absence'];
            } else {
                $excused += $entry['_attributes']['absence'];
            }
        }

        return array($excused, $notexcused, $excused + $notexcused);
    }

    /**
     * Sum up all completed and open outcomes for a given student.
     *
     * @param string $classid   An ID from the class.
     *
     * @param string $studentid An ID from the student contact.
     *
     * @return array  A list with the requested data.
     */
    function sumOutcomes($classid, $studentid)
    {
        $driver = &Skoli_Driver::singleton($classid);
        $entries = current($driver->getEntries($studentid, 'outcome'));

        $completed = 0;
        $open = 0;
        foreach($entries['_entries'] as $entry) {
            if (empty($entry['_attributes']['completed'])) {
                $open++;
            } else {
                $completed++;
            }
        }

        return array($completed, $open, $completed + $open);
    }

    /**
     * Sum up all marks for a given student.
     *
     * @param string $classid   An ID from the class.
     *
     * @param string $studentid An ID from the student contact.
     *
     * @param string $subject   Only sum up marks from this subject.
     *
     * @return float  The requested data.
     */
    function sumMarks($classid, $studentid, $subject = null)
    {
        global $prefs;

        $driver = &Skoli_Driver::singleton($classid);
        if (!is_null($subject)) {
            $params = array(array('name' => 'subject', 'value' => $subject, 'strict' => 1));
        } else {
            $params = null;
        }
        $entries = current($driver->getEntries($studentid, 'mark', $params));

        /* Count weights */
        $totalweight = 0;
        foreach($entries['_entries'] as $entry) {
            $totalweight += Skoli::convertNumber($entry['_attributes']['weight']);
        }

        if ($totalweight <= 0) {
            return '';
        }

        $sum = 0;
        $weight = 100 / $totalweight;
        foreach($entries['_entries'] as $entry) {
            $sum += $weight * Skoli::convertNumber($entry['_attributes']['weight']) * Skoli::convertNumber($entry['_attributes']['mark']);
        }

        if ($sum > 0) {
            return round($sum / 100, $prefs->getValue('marks_roundby'));
        } else {
            return '';
        }
    }

    /**
     * Converts numbers with a comma to a valid php number.
     *
     * @param string $number The number to convert.
     *
     * @return string  The converted number
     */
    function convertNumber($number)
    {
        $number = str_replace(',', '.', $number);
        return $number;
    }

    /**
     * Build Skoli's list of menu items.
     */
    function getMenu()
    {
        global $conf, $registry, $browser, $print_link;

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::url('list.php'), _("List Classes"), 'skoli.png', null, null, null, basename($_SERVER['PHP_SELF']) == 'index.php' ? 'current' : null);
        if (count(Skoli::listClasses(false, Horde_Perms::EDIT))) {
            $menu->add(Horde::url('add.php'), _("_New Entry"), 'add.png', null, null, null, Horde_Util::getFormData('entry') ? '__noselection' : null);
        }

        /* Search. */
        $menu->add(Horde::url('search.php'), _("_Search"), 'search.png', Horde_Themes::img(null, 'horde'));

        /* Import/Export. */
        if ($conf['menu']['export']) {
            $menu->add(Horde::url('data.php'), _("_Export"), 'data.png', Horde_Themes::img(null, 'horde'));
        }

        // @TODO Implement an easy form to create timetables in e.g. Kronolith
        /* Timetable.
         * Show this item only if an application provides 'calendar/show' and we have permissions to view it.
        $app = $registry->hasMethod('calendar/show');
        if ($app !== false && $registry->get('status', $app) != 'inactive' && $registry->hasPermission($app, Horde_Perms::EDIT)) {
            $menu->add(Horde::url(Horde_Util::addParameter('timetable.php', 'actionID', 'new_timetable')), _("_New Timetable"), 'timetable.png');
        }
        */

        return $menu;
    }

    /**
     * Comparison function for sorting classes by semester start date.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_semesterstart($a, $b)
    {
        if ($a['start'] == $b['start'] ) {
            return 0;
        }

        // Treat empty start dates as farthest into the future.
        if ($a['start'] == 0) {
            return 1;
        }
        if ($b['start'] == 0) {
            return -1;
        }

        return ($a['start'] > $b['start']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting classes by semester start date.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater,
     *                  0 if they are equal.
     */
    function _rsort_class_semesterstart($a, $b)
    {
        if ($a['start'] == $b['start']) {
            return 0;
        }

        // Treat empty start dates as farthest into the future.
        if ($a['start'] == 0) {
            return -1;
        }
        if ($b['start'] == 0) {
            return 1;
        }

        return ($a['start'] < $b['start']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting classes by semester end date.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_semesterend($a, $b)
    {
        if ($a['end'] == $b['end'] ) {
            return 0;
        }

        // Treat empty end dates as farthest into the future.
        if ($a['end'] == 0) {
            return 1;
        }
        if ($b['end'] == 0) {
            return -1;
        }

        return ($a['end'] > $b['end']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting classes by semester end date.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater,
     *                  0 if they are equal.
     */
    function _rsort_class_semesterend($a, $b)
    {
        if ($a['end'] == $b['end']) {
            return 0;
        }

        // Treat empty end dates as farthest into the future.
        if ($a['end'] == 0) {
            return -1;
        }
        if ($b['end'] == 0) {
            return 1;
        }

        return ($a['end'] < $b['end']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting classes by name.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_name($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    /**
     * Comparison function for reverse sorting classes by name.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater;
     *                  0 if they are equal.
     */
    function _rsort_class_name($a, $b)
    {
        return strcasecmp($b['name'], $a['name']);
    }

    /**
     * Comparison function for sorting classes by grade.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_grade($a, $b)
    {
        return strcasecmp($a['grade'], $b['grade']);
    }

    /**
     * Comparison function for reverse sorting classes by grade.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater;
     *                  0 if they are equal.
     */
    function _rsort_class_grade($a, $b)
    {
        return strcasecmp($b['grade'], $a['grade']);
    }

    /**
     * Comparison function for sorting classes by semester.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_semester($a, $b)
    {
        return strcasecmp($a['semester'], $b['semester']);
    }

    /**
     * Comparison function for reverse sorting classes by semester.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater;
     *                  0 if they are equal.
     */
    function _rsort_class_semester($a, $b)
    {
        return strcasecmp($b['semester'], $a['semester']);
    }

    /**
     * Comparison function for sorting classes by location.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_location($a, $b)
    {
        return strcasecmp($a['location'], $b['location']);
    }

    /**
     * Comparison function for reverse sorting classes by location.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater;
     *                  0 if they are equal.
     */
    function _rsort_class_location($a, $b)
    {
        return strcasecmp($b['location'], $a['location']);
    }

    /**
     * Comparison function for sorting classes by category.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  1 if class one is greater, -1 if class two is greater;
     *                  0 if they are equal.
     */
    function _sort_class_category($a, $b)
    {
        return strcasecmp($a['category'] ? $a['category'] : _("Unfiled"),
                          $b['category'] ? $b['category'] : _("Unfiled"));
    }

    /**
     * Comparison function for reverse sorting classes by category.
     *
     * @param array $a  Class one.
     * @param array $b  Class two.
     *
     * @return integer  -1 if class one is greater, 1 if class two is greater;
     *                  0 if they are equal.
     */
    function _rsort_class_category($a, $b)
    {
        return strcasecmp($b['category'] ? $b['category'] : _("Unfiled"),
                          $a['category'] ? $a['category'] : _("Unfiled"));
    }

    /**
     * Comparison function for sorting students by name.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  1 if student one is greater, -1 if student two is greater;
     *                  0 if they are equal.
     */
    function _sort_student_name($a, $b)
    {
        return strcasecmp($a['name'], $b['name']);
    }

    /**
     * Comparison function for reverse sorting students by name.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  -1 if student one is greater, 1 if student two is greater;
     *                  0 if they are equal.
     */
    function _rsort_student_name($a, $b)
    {
        return strcasecmp($b['name'], $a['name']);
    }

    /**
     * Comparison function for sorting students by last entry date.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  1 if student one is greater, -1 if student two is greater;
     *                  0 if they are equal.
     */
    function _sort_student_lastentry($a, $b)
    {
        // Treat empty dates as farthest into the past.
        if (!isset($a['_lastentry']) || $a['_lastentry'] == 0) {
            return -1;
        }
        if (!isset($b['_lastentry']) || $b['_lastentry'] == 0) {
            return 1;
        }

        if ($a['_lastentry'] == $b['_lastentry'] ) {
            return 0;
        }

        return ($a['_lastentry'] > $b['_lastentry']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting students by last entry date.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  -1 if student one is greater, 1 if student two is greater,
     *                  0 if they are equal.
     */
    function _rsort_student_lastentry($a, $b)
    {
        // Treat empty dates as farthest into the past.
        if (!isset($a['_lastentry']) || $a['_lastentry'] == 0) {
            return 1;
        }
        if (!isset($b['_lastentry']) || $b['_lastentry'] == 0) {
            return -1;
        }

        if ($a['_lastentry'] == $b['_lastentry']) {
            return 0;
        }

        return ($a['_lastentry'] < $b['_lastentry']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting students by sumabsences.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  1 if student one is greater, -1 if student two is greater;
     *                  0 if they are equal.
     */
    function _sort_student_sumabsences($a, $b)
    {
        if ($a['_sumabsences'] == $b['_sumabsences'] ) {
            return 0;
        }

        return ($a['_sumabsences'] > $b['_sumabsences']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting students by sumabsences.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  -1 if student one is greater, 1 if student two is greater,
     *                  0 if they are equal.
     */
    function _rsort_student_sumabsences($a, $b)
    {
        if ($a['_sumabsences'] == $b['_sumabsences']) {
            return 0;
        }

        return ($a['_sumabsences'] < $b['_sumabsences']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting students by summarks.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  1 if student one is greater, -1 if student two is greater;
     *                  0 if they are equal.
     */
    function _sort_student_summarks($a, $b)
    {
        // Treat empty sums as lowest mark.
        if ($a['_summarks'] == '') {
            return -1;
        }
        if ($b['_summarks'] == '') {
            return 1;
        }

        if ($a['_summarks'] == $b['_summarks'] ) {
            return 0;
        }

        return ($a['_summarks'] > $b['_summarks']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting students by summarks.
     *
     * @param array $a  Student one.
     * @param array $b  Student two.
     *
     * @return integer  -1 if student one is greater, 1 if student two is greater,
     *                  0 if they are equal.
     */
    function _rsort_student_summarks($a, $b)
    {
        // Treat empty sums as lowest mark.
        if ($a['_summarks'] == '') {
            return 1;
        }
        if ($b['_summarks'] == '') {
            return -1;
        }

        if ($a['_summarks'] == $b['_summarks']) {
            return 0;
        }

        return ($a['_summarks'] < $b['_summarks']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting entries by date.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  1 if entry one is greater, -1 if entry two is greater;
     *                  0 if they are equal.
     */
    function _sort_entry_date($a, $b)
    {
        if ($a['timestamp'] == $b['timestamp'] ) {
            return 0;
        }

        return ($a['timestamp'] > $b['timestamp']) ? -1 : 1;
    }

    /**
     * Comparison function for reverse sorting entries by date.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  -1 if entry one is greater, 1 if entry two is greater,
     *                  0 if they are equal.
     */
    function _rsort_entry_date($a, $b)
    {
        if ($a['timestamp'] == $b['timestamp']) {
            return 0;
        }

        return ($a['timestamp'] < $b['timestamp']) ? -1 : 1;
    }

    /**
     * Comparison function for sorting entries by class.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  1 if entry one is greater, -1 if entry two is greater;
     *                  0 if they are equal.
     */
    function _sort_entry_class($a, $b)
    {
        if ($a['class'] == $b['class'] ) {
            return Skoli::_sort_entry_date($a, $b);
        }

        return ($a['class'] > $b['class']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting entries by class.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  -1 if entry one is greater, 1 if entry two is greater,
     *                  0 if they are equal.
     */
    function _rsort_entry_class($a, $b)
    {
        if ($a['class'] == $b['class']) {
            return Skoli::_rsort_entry_date($a, $b);
        }

        return ($a['class'] < $b['class']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting entries by student.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  1 if entry one is greater, -1 if entry two is greater;
     *                  0 if they are equal.
     */
    function _sort_entry_student($a, $b)
    {
        if ($a['student'] == $b['student'] ) {
            return Skoli::_sort_entry_date($a, $b);
        }

        return ($a['student'] > $b['student']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting entries by student.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  -1 if entry one is greater, 1 if entry two is greater,
     *                  0 if they are equal.
     */
    function _rsort_entry_student($a, $b)
    {
        if ($a['student'] == $b['student']) {
            return Skoli::_rsort_entry_date($a, $b);
        }

        return ($a['student'] < $b['student']) ? 1 : -1;
    }

    /**
     * Comparison function for sorting entries by type.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  1 if entry one is greater, -1 if entry two is greater;
     *                  0 if they are equal.
     */
    function _sort_entry_type($a, $b)
    {
        if ($a['type'] == $b['type'] ) {
            return Skoli::_sort_entry_date($a, $b);
        }

        return ($a['type'] > $b['type']) ? 1 : -1;
    }

    /**
     * Comparison function for reverse sorting entries by type.
     *
     * @param array $a  Entry one.
     * @param array $b  Entry two.
     *
     * @return integer  -1 if entry one is greater, 1 if entry two is greater,
     *                  0 if they are equal.
     */
    function _rsort_entry_type($a, $b)
    {
        if ($a['type'] == $b['type']) {
            return Skoli::_rsort_entry_date($a, $b);
        }

        return ($a['type'] < $b['type']) ? 1 : -1;
    }
}
