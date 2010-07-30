<?php
/**
 * Horde_Form for creating classes.
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
 * The Skoli_CreateClassForm class provides the form for
 * creating a class.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_CreateClassForm extends Horde_Form {

    /**
     * Name of the new share.
     *
     * @var int
     */
    var $shareid;

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


    function Skoli_CreateClassForm(&$vars)
    {
        global $conf, $prefs, $registry;

        parent::Horde_Form($vars, _("Create Class"));

        $this->setSection('properties', _("Properties"));

        $this->addVariable(_("General Settings"), null, 'header', false);

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->addVariable(_("Category"), 'category', 'category', false);
        // A new category doesn't survive a reload action, so reset it
        // @TODO: Could this be a bug?
        if (strpos($this->_vars->get('category'), '*new*') !== false) {
            $this->_vars->set('category', $this->_vars->get('new_category'));
        }

        $this->addVariable(_("School Specific Settings"), null, 'header', false);

        // Load Skoli_School
        require_once SKOLI_BASE . '/lib/School.php';

        // List schools
        $schoollist = Skoli_School::listSchools();
        $actionvariable = &$this->addVariable(_("Schools"), 'school', 'enum', true, count($schoollist)>1 ? false : true, null, array($schoollist, _("Choose:")));
        if (count($schoollist) > 1) {
            require_once 'Horde/Form/Action.php';
            $actionvariable->setAction(Horde_Form_Action::factory('reload'));
        } else {
            $this->_vars->set('school', key($schoollist));
        }

        // Load the selected school
        if ($this->_vars->exists('school')) {
            $school = new Skoli_School($this->_vars->get('school'));
            foreach ($this->_schoolproperties as $name) {
                $school->addFormVariable($this, $name);
            }
        }

        $this->setSection('students', _("Students"));

        $this->addVariable(_("Address Book"), null, 'header', false);

        $addressbooklist = Skoli_School::listAddressBooks();
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

        if ($conf['addresses']['contact_list'] != 'none' && $prefs->getValue('contact_list') != 'none') {
            $this->addVariable(_("Contact List"), null, 'header', false);
            if ($conf['addresses']['contact_list'] == 'user' && $prefs->getValue('contact_list') == 'ask') {
                $this->addVariable(_("Create Contact List?"), 'contact_list_create', 'boolean', true, false);
                if (!$this->_vars->exists('contact_list')) {
                    $this->_vars->set('contact_list_create', true);
                }
            }
            $this->addVariable(_("Name"), 'contact_list', 'text', false,
                $conf['addresses']['contact_list'] == 'auto' || $prefs->getValue('contact_list') == 'auto' ? true : false, _("The substitutions %c, %g or %s will be replaced automatically by the class, grade respectively semester name."));
            if (!$this->_vars->exists('contact_list')) {
                $contactlist = $conf['addresses']['contact_list'] == 'auto' ? $conf['addresses']['contact_list_name'] : $prefs->getValue('contact_list_name');
            } else {
                $contactlist = $this->_vars->get('contact_list');
            }
            $this->_vars->set('contact_list', Skoli_School::parseContactListName($contactlist, $this->_vars));
        }

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        global $conf, $prefs, $registry, $notification;

        /* Add new category. */
        if (strpos($this->_vars->get('category'), '*new*') !== false || $this->_vars->get('category') == $this->_vars->get('new_category')) {
            $cManager = new Horde_Prefs_CategoryManager();
            $cManager->add($this->_vars->get('new_category'));
            $this->_vars->set('category', $this->_vars->get('new_category'));
        }

        // Create new share.
        $this->shareid = strval(new Horde_Support_Uuid());
        $class = $GLOBALS['skoli_shares']->newShare($this->shareid);
        if (is_a($class, 'PEAR_Error')) {
            return $class;
        }
        $class->set('name', $this->_vars->get('name'));
        $class->set('desc', $this->_vars->get('description'));
        $class->set('category', $this->_vars->get('category'));
        $class->set('school', $this->_vars->get('school'));
        $class->set('address_book', $this->_vars->get('address_book'));

        require_once 'Horde/Date.php';
        foreach ($this->_schoolproperties as $property) {
            if ($property == 'start' || $property == 'end') {
                $date = new Horde_Date($this->_vars->get($property));
                $this->_vars->set($property, $date->datestamp());
            } else if ($property == 'marks' && $this->_vars->get($property . '_custom') != '') {
                $this->_vars->set($property, $this->_vars->get($property . '_custom'));
            }
            $class->set($property, $this->_vars->get($property) == '' ? null : $this->_vars->get($property));
        }

        $result = $GLOBALS['skoli_shares']->addShare($class);

        // Save students
        if ($this->_vars->exists('students') && $result) {
            $driver = &Skoli_Driver::singleton($this->shareid);
            $result = $driver->addStudents($this->_vars->get('students'));
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(_("Couldn't add the selected students to the class."), 'horde.warning');
            }

            // Add new contact list
            if ($conf['addresses']['contact_list'] != 'none' && $prefs->getValue('contact_list') != 'none' && $this->_vars->get('contact_list') != '') {
                $createlist = true;
                if ($conf['addresses']['contact_list'] == 'user' && $prefs->getValue('contact_list') == 'ask' && $this->_vars->get('contact_list_create') == '') {
                    $createlist = false;
                }
            } else {
                $createlist = false;
            }
            if ($createlist) {
                $apiargs = array(
                    'content' => array(
                        '__type' => 'Group',
                        '__members' => serialize($this->_vars->get('students')),
                        'name' => Skoli_School::parseContactListName($this->_vars->get('contact_list'), $this->_vars, true),
                    ),
                    'contentType' => 'array',
                    'source' => $this->_vars->get('address_book')
                );

                try {
                    $registry->call('contacts/import', $apiargs);
                } catch (Horde_Exception $e) {
                    $notification->push(sprintf(_("Couldn't create the contact list \"%s\"."), $this->_vars->get('contact_list')), 'horde.warning');
                }
            }
        }

        return $result;
    }

}
