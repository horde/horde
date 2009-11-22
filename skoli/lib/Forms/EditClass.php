<?php
/**
 * Horde_Form for editing classs.
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
 * The Skoli_EditClassForm class provides the form for
 * editing a class.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_EditClassForm extends Horde_Form {

    /**
     * Class being edited
     */
    var $_class;

    /**
     * List of school properties.
     *
     * @var array
     */
    var $_schoolproperties = array(
        'grade',
        'semester',
        'start',
        'end',
        'location',
        'marks');

    function Skoli_EditClassForm(&$vars, &$class)
    {
        global $conf, $prefs, $registry;

        $this->_class = &$class;

        parent::Horde_Form($vars, sprintf(_("Edit %s"), $class->get('name')));

        $this->addHidden('', 'c', 'text', true);

        $this->setSection('properties', _("Properties"));

        $this->addVariable(_("General Settings"), null, 'header', false);

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->addVariable(_("Category"), 'category', 'category', false);

        $this->addVariable(_("School Specific Settings"), null, 'header', false);

        // Load Skoli_School
        require_once SKOLI_BASE . '/lib/School.php';

        // List schools
        $schoollist = Skoli_School::listSchools();
        $this->addVariable(_("School"), 'school', 'enum', true, true, null, array($schoollist, _("Choose:")));

        $school = new Skoli_School($this->_vars->get('school'));
        foreach ($this->_schoolproperties as $name) {
            $school->addFormVariable($this, $name);
        }

        $this->setSection('students', _("Students"));

        $this->addVariable(_("Address Book"), null, 'header', false);

        $addressbooklist = Skoli_School::listAddressBooks(true);
        $actionvariable = &$this->addVariable(_("Address Book"), 'address_book', 'enum', true, count($addressbooklist)>1 ? false : true, null, array($addressbooklist, _("Choose:")));
        if (count($addressbooklist) > 1) {
            require_once 'Horde/Form/Action.php';
            $actionvariable->setAction(Horde_Form_Action::factory('reload'));
        } else {
            $this->_vars->set('address_book', key($addressbooklist));
        }

        $this->addVariable(_("Students"), null, 'header', false);

        if ($this->_vars->get('address_book') != '') {
            $searchargs = array(
                'addresses' => array(''),
                'addressbooks' => array($this->_vars->get('address_book')),
                'fields' => array()
            );
            if ($search_fields_pref = $prefs->getValue('search_fields')) {
                foreach (explode("\n", $search_fields_pref) as $s) {
                    $s = trim($s);
                    $s = explode("\t", $s);
                    if (!empty($s[0]) && ($s[0] == $this->_vars->get('address_book'))) {
                        $searchargs['fields'][array_shift($s)] = $s;
                        break;
                    }
                }
            }
            $resultstmp = $registry->call('contacts/search', $searchargs);
            // contacts/search seems to return an array entry for each source.
            $results = array();
            foreach ($resultstmp as $r) {
                $results = array_merge($results, $r);
            }
            foreach ($results as $address) {
                if (isset($address['__type']) && $address['__type'] == 'Object') {
                    $addresses[$address['__key']] = $address[$conf['addresses']['name_field']];
                }
            }
        } else {
            $addresses = array();
        }

        $this->addVariable(_("Students"), 'students', 'multienum', false, false, null, array($addresses, 20));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        global $conf, $prefs, $registry, $notification;

        /* Add new category. */
        if (strpos($this->_vars->get('category'), '*new*') !== false || $this->_vars->get('category') == $this->_vars->get('new_category')) {
            require_once 'Horde/Prefs/CategoryManager.php';
            $cManager = new Prefs_CategoryManager();
            $cManager->add($this->_vars->get('new_category'));
            $this->_vars->set('category', $this->_vars->get('new_category'));
        }

        $this->_class->set('name', $this->_vars->get('name'));
        $this->_class->set('desc', $this->_vars->get('description'));
        $this->_class->set('category', $this->_vars->get('category'));
        $this->_class->set('address_book', $this->_vars->get('address_book'));

        require_once 'Horde/Date.php';
        foreach ($this->_schoolproperties as $property) {
            if ($property == 'start' || $property == 'end') {
                $date = new Horde_Date($this->_vars->get($property));
                $this->_vars->set($property, $date->datestamp());
            } else if ($property == 'marks' && $this->_vars->get($property . '_custom') != '') {
                $this->_vars->set($property, $this->_vars->get($property . '_custom'));
            }
            $this->_class->set($property, $this->_vars->get($property) == '' ? null : $this->_vars->get($property));
        }

        // Save students
        $driver = &Skoli_Driver::singleton($this->_vars->get('c'));
        $result = $driver->addStudents($this->_vars->get('students'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(_("Couldn't add the selected students to the class."), 'horde.warning');
        }

        $result = $this->_class->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save class \"%s\": %s"), $id, $result->getMessage()));
        }
        return true;
    }

}
