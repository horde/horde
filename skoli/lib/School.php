<?php
/**
 * Skoli School Class.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Martin Blumenthal <tinu@humbapa.ch>
 * @package Skoli
 */
class Skoli_School {

    /**
     * School list from template.
     *
     * @var array
     */
    public static $schools;

    /**
     * Current school from template.
     *
     * @var array
     */
    var $school;

    /**
     * Load the school list from template.
     */
    function Skoli_School($schoolName)
    {
        self::_loadSchools();
        if (!isset(self::$schools[$schoolName]) || !is_array(self::$schools[$schoolName])) {
            return PEAR::raiseError(sprintf(_("Error loading the school \"%s\" from template."), $schoolName));
        } else {
            $this->school = self::$schools[$schoolName];
        }
    }

    /**
     * Adds a variable to the current form.
     *
     * @param Horde_Form $form  The current form.
     *
     * @param string $property  The property to add.
     *
     * @param array $params     Property dependent parameters.
     */
    function addFormVariable(&$form, $property, $params = array())
    {
        switch ($property) {
        case 'start':
        case 'end':
            $form->addVariable(_(ucfirst($property)), $property, 'monthdayyear', true, false, null, array(date('Y') - 10));
            if ($form->_vars->exists('semester') && isset($this->school['semester']) && is_array($this->school['semester'])) {
                foreach ($this->school['semester'] as $semester) {
                    if ($semester['name'] == $form->_vars->get('semester')) {
                        $activesemester = $semester;
                        break;
                    }
                }
                $datevars = $form->_vars->get($property);
                if (isset($activesemester[$property]) && empty($datevars['day'])) {
                    require_once 'Horde/Date.php';
                    if ($property == 'start') {
                        $startdate = 0;
                    } else {
                        $startdate = new Horde_Date($form->_vars->get('start'));
                        $startdate = $startdate->datestamp();
                    }
                    $date = new Horde_Date($this->_getSemesterTime($activesemester[$property], $startdate));
                    $form->_vars->set($property, array('month' => $date->month, 'day' => $date->mday, 'year' => $date->year));
                }
            }
            break;

        case 'marks':
            $marksformat = array(
                'numbers' => _("Format in numbers"),
                'percent' => _("Format in percent"),
                'custom'  => _("Custom format:")
            );
            if (isset($this->school[$property])) {
                $form->_vars->set($property, $this->school[$property]);
                if (!isset($marksformat[$this->school[$property]])) {
                    $marksformat['custom'] .= ' ' . $this->school[$property];
                    $marksformat[$this->school[$property]] = $marksformat['custom'];
                    unset($marksformat['custom']);
                }
                $form->addVariable(_(ucfirst($property)), $property, 'enum', true, true, null, array($marksformat, _("Choose:")));
            } else {
                require_once 'Horde/Form/Action.php';
                if ($form->_vars->exists($property) && !isset($marksformat[$form->_vars->get($property)])) {
                    $form->_vars->set($property . '_custom', $form->_vars->get($property));
                    $form->_vars->set($property, 'custom');
                }
                $actionvariable = &$form->addVariable(_(ucfirst($property)), $property, 'enum', true, false, null, array($marksformat, _("Choose:")));
                $actionvariable->setAction(Horde_Form_Action::factory('reload'));
                if ($form->_vars->get($property) == 'custom') {
                    $form->addVariable('', $property . '_custom', 'text', true, false, _("List with custom marks separated by comma (best mark first)"));
                }
            }
            break;

        case 'subject':
            $obligatory = isset($params[0]) ? $params[0] : true;
            $onlywithobjectives = isset($params[1]) ? $params[1] : false;
            if (isset($this->school['subjects'])) {
                $values = array();
                foreach ($this->school['subjects'] as $key=>$value) {
                    if (!$onlywithobjectives || ($onlywithobjectives && is_array($value))) {
                        $subject = is_array($value) ? $key : $value;
                        $values[$subject] = $subject;
                    }
                }
                if ($onlywithobjectives) {
                    if (count($values) > 0) {
                        require_once 'Horde/Form/Action.php';
                        $actionvariable = &$form->addVariable(_(ucfirst($property)), 'attribute_subject', 'enum', $obligatory, false, null, array(array_merge(array(_("Interdisciplinary")=>_("Interdisciplinary")), $values)));
                        $actionvariable->setAction(Horde_Form_Action::factory('reload'));
                    } else {
                        $form->addVariable(_(ucfirst($property)), 'attribute_subject', 'text', true, true);
                        $form->_vars->set('attribute_subject', _("Interdisciplinary"));
                    }
                } else {
                    $form->addVariable(_(ucfirst($property)), 'attribute_subject', 'enum', $obligatory, false, null, array($values, _("Choose:")));
                }
            } else {
                $form->addVariable(_(ucfirst($property)), 'attribute_subject', 'text', $obligatory, false);
            }
            break;

        case 'category':
            $subject = !empty($params[0]) ? $params[0] : _("Interdisciplinary");
            if ($subject != _("Interdisciplinary") && isset($this->school['subjects'][$subject]) && is_array($this->school['subjects'][$subject])) {
                $values = array();
                foreach ($this->school['subjects'][$subject] as $value) {
                    $values[$value] = $value;
                }
                $form->addVariable(_(ucfirst($property)), 'attribute_category', 'enum', true, false, null, array($values, _("Choose:")));
            } else if ($subject == _("Interdisciplinary") && isset($this->school['objectives'])) {
                $values = array();
                foreach ($this->school['objectives'] as $value) {
                    $values[$value] = $value;
                }
                $form->addVariable(_(ucfirst($property)), 'attribute_category', 'enum', true, false, null, array($values, _("Choose:")));
            } else {
                $form->addVariable(_(ucfirst($property)), 'attribute_category', 'text', true, false);
            }
        break;

        default:
            if (isset($this->school[$property]) && is_array($this->school[$property])) {
                if (count($this->school[$property]) > 1) {
                    $values = array();
                    foreach ($this->school[$property] as $value) {
                        $key = is_array($value) ? $value['name'] : $value;
                        $values[$key] = $key;
                    }
                    if (is_array(current($this->school[$property]))) {
                        require_once 'Horde/Form/Action.php';
                        $actionvariable = &$form->addVariable(_(ucfirst($property)), $property, 'enum', false, false, null, array($values, _("Choose:")));
                        $actionvariable->setAction(Horde_Form_Action::factory('reload'));
                    } else {
                        $form->addVariable(_(ucfirst($property)), $property, 'enum', false, false, null, array($values, _("Choose:")));
                    }
                } else {
                    $form->addVariable(_(ucfirst($property)), $property, 'text', false, true);
                    $value = current($this->school[$property]);
                    $form->_vars->set($property, is_array($value) ? $value['name'] : $value);
                }
            } else {
                $form->addVariable(_(ucfirst($property)), $property, 'text', false, false);
            }
        }
    }

    /**
     * Returns a timestamp for the specified semester start- or enddate.
     *
     * @param mixed  The dateformat specified in conf/schools.php for this date.
     *
     * @return int  The timestamp.
     */
    private function _getSemesterTime($format, $startdate)
    {
        if (is_int($format)) {
            // Timestamp format
            $timestamp = $format;
        } else if (preg_match('/^W([0-9]{2})\-[0-9]$/', $format, $m)) {
            $year = date('Y');
            if (date('W') > $m[1]) {
                $year++;
            }
            $timestamp = strtotime($year . '-' . $format);
        } else {
            $timestamp = strtotime($format);
        }
        if (is_int($timestamp) && $timestamp > 0) {
            if ($startdate >= $timestamp) {
                $timestamp = strtotime('+1 year', $timestamp);
            }
            return $timestamp;
        } else {
            return '';
        }
    }

    /**
     * Returns all schools specified in conf/schools.php.
     *
     * @return array  The school list.
     */
    public static function listSchools()
    {
        self::_loadSchools();
        $schools = array();
        foreach (self::$schools as $key=>$val) {
            $schools[$key] = $val['title'];
        }
        return $schools;
    }

    /**
     * Loads the schools specified in conf/schools.php
     */
    private static function _loadSchools()
    {
        if (!isset(self::$schools)) {
            require_once SKOLI_BASE . '/config/schools.php';
            self::$schools = $cfgSchools;
        }
    }

    /**
     * Returns all addressbooks skoli is defined to use.
     *
     * @param boolean $all  If set to true return all addressbooks a user has access to.
     *
     * @return array  The address book list.
     */
    public static function listAddressBooks($all = false)
    {
        global $conf, $prefs, $registry;

        $addressbooks = $registry->call('contacts/sources');

        if (!$all && $conf['addresses']['storage'] == 'custom') {
            if (isset($addressbooks[$conf['addresses']['address_book']])) {
                $addressbooks = array($conf['addresses']['address_book'] => $addressbooks[$conf['addresses']['address_book']]);
            } else {
                $addressbooks = array();
            }
        }

        return $addressbooks;
    }

    /**
     * Returns the parsed contact list name.
     *
     * @param string $contactlist    The contact list name to parse.
     *
     * @param Horde_Variables $vars  The variables to use as replacement.
     *
     * @param boolean $force         If set to true also replaces empty fields.
     *
     * @return string  The parsed contact list name.
     */
    public static function parseContactListName($contactlist, $vars, $force = false)
    {
        $contactlistsubs = array(
            '%c' => 'name',
            '%g' => 'grade',
            '%s' => 'semester'
        );
        foreach ($contactlistsubs as $pattern=>$field) {
            if (strpos($contactlist, $pattern) !== false && ($vars->get($field) != '' || $force)) {
                $contactlist = str_replace($pattern, $vars->get($field), $contactlist);
            }
        }
        return $contactlist;
    }

}
