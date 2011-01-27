<?php
/**
 * @package Turba
 */
class Turba_Form_Contact extends Horde_Form
{
    /**
     * @param array $vars  Array of form variables
     * @param Turba_Object $contact
     */
    public function __construct($vars, Turba_Object $contact, $tabs = true, $title = null)
    {
        global $conf, $notification;

        if (is_null($title)) {
            $title = 'Turba_View_Contact';
        }
        parent::__construct($vars, '', $title);

        /* Get the values through the Turba_Object class. */
        $object = array();

        foreach ($contact->driver->getCriteria() as $info_key => $info_val) {
            $object[$info_key] = $contact->getValue($info_key);
        }
        $vars->set('object', $object);

        $this->_addFields($contact, $tabs);

        /* List files. */
        $v_params = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->getConfig('documents');
        if ($v_params['type'] != 'none') {
            try {
                $files = $contact->listFiles();
                $this->addVariable(_("Files"), '__vfs', 'html', false);
                $vars->set('__vfs', implode('<br />', array_map(array($contact, 'vfsEditUrl'), $files)));
            } catch (Turba_Exception $e) {
                $notification->push($files, 'horde.error');
            }
        }
    }

    /**
     * Set up the Horde_Form fields for $contact's attributes.
     *
     * @param Turba_Object $contact  The contact
     */
    public function _addFields(Turba_Object $contact, $useTabs = true)
    {
        // @TODO: inject this
        global $attributes;

        // Run through once to see what form actions, if any, we need
        // to set up.
        $actions = array();
        $map = $contact->driver->map;
        $fields = array_keys($contact->driver->getCriteria());
        foreach ($fields as $field) {
            if (is_array($map[$field])) {
                foreach ($map[$field]['fields'] as $action_field) {
                    if (!isset($actions[$action_field])) {
                        $actions[$action_field] = array();
                    }
                    $actions[$action_field]['fields'] = $map[$field]['fields'];
                    $actions[$action_field]['format'] = $map[$field]['format'];
                    $actions[$action_field]['target'] = $field;
                }
            }
        }

        // Now run through and add the form variables.
        $tabs = $contact->driver->tabs;
        if (!count($tabs)) {
            $tabs = array('' => $fields);
        }
        foreach ($tabs as $tab => $tab_fields) {
            if (!empty($tab)) {
                if ($useTabs) {
                    $this->setSection($tab, $tab);
                } else {
                    $this->addVariable($tab, '', 'header', false);
                }
            }
            foreach ($tab_fields as $field) {
                if (!in_array($field, $fields) ||
                    !isset($attributes[$field])) {
                    continue;
                }

                $attribute = $attributes[$field];
                $params = isset($attribute['params']) ? $attribute['params'] : array();
                $desc = isset($attribute['desc']) ? $attribute['desc'] : null;

                if (is_array($map[$field])) {
                    $v = $this->addVariable($attribute['label'], 'object[' . $field . ']', $attribute['type'], false, false, $desc, $params);
                    $v->disable();
                } else {
                    $readonly = isset($attribute['readonly']) ? $attribute['readonly'] : null;
                    $v = $this->addVariable($attribute['label'], 'object[' . $field . ']', $attribute['type'], $attribute['required'], $readonly, $desc, $params);

                    if (!empty($actions[$field])) {
                        $actionfields = array();
                        foreach ($actions[$field]['fields'] as $f) {
                            $actionfields[] = 'object[' . $f . ']';
                        }
                        $a = Horde_Form_Action::factory('updatefield',
                                                        array('format' => $actions[$field]['format'],
                                                              'target' => 'object[' . $actions[$field]['target'] . ']',
                                                              'fields' => $actionfields));
                        $v->setAction($a);
                    }
                }

                if (isset($attribute['default'])) {
                    $v->setDefault($attribute['default']);
                }
            }
        }
    }

}
