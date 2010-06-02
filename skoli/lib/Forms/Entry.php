<?php
/**
 * Horde_Form for adding and updateing entries.
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Skoli
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Skoli_EntryForm class provides the form for
 * adding and updateing a new entry.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_EntryForm extends Horde_Form {

    function Skoli_EntryForm(&$vars)
    {
        global $conf, $prefs, $registry;

        $update = $vars->exists('entry') && $vars->exists('view');

        if ($vars->get('view') != 'Entry') {
            parent::Horde_Form($vars, $update ? _("Update Entry") : _("Add Entry"));
        } else {
            parent::Horde_Form($vars);
        }

        if ($update) {
            $this->addHidden('', 'entry', 'text', true);
            $this->addHidden('', 'view', 'text', true);
        }

        $classes = Skoli::listClasses(false, Horde_Perms::EDIT);
        $classes_enums = array();
        foreach ($classes as $class) {
            if ($class->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
                $classes_enums[$class->getName()] = $class->get('name');
            }
        }

        if (!$this->_vars->exists('class_id') && $vars->exists('class')) {
            $this->_vars->set('class_id', $vars->get('class'));
            if (!$this->_vars->exists('student_id') && $vars->exists('student')) {
                $this->_vars->set('student_id', array($vars->get('student')));
            } else {
                $this->_vars->set('student_id', array());
            }
        }

        // List classes
        $actionvariable = &$this->addVariable(_("Class"), 'class_id', 'enum', true, count($classes)>1 ? false : true, null, array($classes_enums, _("Choose:")));
        if (count($classes) > 1) {
            require_once 'Horde/Form/Action.php';
            $actionvariable->setAction(Horde_Form_Action::factory('reload'));
        } else {
            reset($classes);
            $this->addHidden('', 'class_id', 'text', true);
            $this->_vars->set('class_id', key($classes));
        }

        // Load the selected students        
        if ($this->_vars->get('class_id') != '') {
            $class = current(Skoli::listStudents($vars->get('class_id')));
            foreach ($class['_students'] as $address) {
                $addresses[$address['student_id']] = $address[$conf['addresses']['name_field']];
            }
            if ($update) {
                $this->addVariable(_("Student"), 'student_id', 'enum', true, false, null, array($addresses));
            } else {
                $this->addVariable(_("Student"), 'student_id', 'multienum', true, false, null, array($addresses, 14));
            }
        } else {
            $addresses = array();
        }

        $this->addVariable(_("Date"), 'object_time', 'monthdayyear', true, false, null, array(date('Y') - 10));
        if (!$this->_vars->exists('object_time')) {
            $date = new Horde_Date(time());
            $this->_vars->set('object_time', array('month' => $date->month, 'day' => $date->mday, 'year' => $date->year));
        }

        // Load last type from preferences
        if (!$this->_vars->exists('object_type')) {
            $this->_vars->set('object_type', $prefs->getValue('default_objects_format'));
        }
        if ($conf['objects']['allow_marks']) {
            $types['mark'] = _("Mark");
        }
        if ($conf['objects']['allow_objectives']) {
            $types['objective'] = _("Objective");
        }
        if ($conf['objects']['allow_outcomes']) {
            $types['outcome'] = _("Outcome");
        }
        if ($conf['objects']['allow_absences']) {
            $types['absence'] = _("Absence");
        }
        $actionvariable = &$this->addVariable(_("Type"), 'object_type', 'radio', true, false, null, array($types));
        require_once 'Horde/Form/Action.php';
        $actionvariable->setAction(Horde_Form_Action::factory('reload'));

        if ($this->_vars->get('object_type') != '' && $this->_vars->get('class_id') != '') {
            switch ($this->_vars->get('object_type')) {

            case 'mark':
                $this->addVariable(_("Title"), 'attribute_title', 'text', true, false);
                switch ($class['marks']) {
                case 'numbers':
                    $this->addVariable(_("Mark"), 'attribute_mark', 'number', true, false, _("Mark in numbers"));
                    break;

                case 'percent':
                    $this->addVariable(_("Mark"), 'attribute_mark', 'number', true, false, _("Mark in percent"));
                    break;

                default:
                    $marks_enums = preg_split('/\s*,\s*/', $class['marks']);
                    $this->addVariable(_("Mark"), 'attribute_mark', 'enum', true, false, null, array(array_combine($marks_enums, $marks_enums), _("Choose:")));
                }
                // Load Skoli_School
                require_once SKOLI_BASE . '/lib/School.php';
                $school = new Skoli_School($class['school']);
                $school->addFormVariable($this, 'subject', array(true));
                $this->addVariable(_("Weight"), 'attribute_weight', 'number', true, false);
                if (!$this->_vars->exists('attribute_weight')) {
                    $this->_vars->set('attribute_weight', 1);
                }
                break;

            case 'objective':
                // Load Skoli_School
                require_once SKOLI_BASE . '/lib/School.php';
                $school = new Skoli_School($class['school']);
                $school->addFormVariable($this, 'subject', array(false, true));
                $school->addFormVariable($this, 'category', array($this->_vars->get('attribute_subject')));
                $this->addVariable(_("Objective"), 'attribute_objective', 'longtext', true, false, null, array(4, 60));
                break;

            case 'outcome':
                $this->addVariable(_("Outcome"), 'attribute_outcome', 'longtext', true, false, null, array(2, 60));
                $this->addVariable(_("Completed?"), 'attribute_completed', 'boolean', true, false);
                $this->addVariable(_("Comment"), 'attribute_comment', 'longtext', false, false, null, array(4, 60));
                break;

            case 'absence':
                $this->addVariable(_("Absence"), 'attribute_absence', 'number', true, false, _("Absence in number of lessons"));
                $this->addVariable(_("Excused?"), 'attribute_excused', 'boolean', true, false);
                if (!$this->_vars->exists('attribute_absence')) {
                    $this->_vars->set('attribute_excused', true);
                }
                $this->addVariable(_("Comment"), 'attribute_comment', 'longtext', false, false, null, array(4, 60));
                break;
            }
        }

        $this->setButtons(array($update ? _("Save") : _("Add")));
    }

    function execute()
    {
        global $conf, $prefs, $registry, $notification;

        // Save last type to preferences
        $prefs->setValue('default_objects_format', $this->_vars->get('object_type'));

        $driver = &Skoli_Driver::singleton($this->_vars->get('class_id'));
        $result = $driver->addEntry($this->_vars);
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(_("Couldn't add the new entry."), 'horde.warning');
        }

        return $result;
    }
}
