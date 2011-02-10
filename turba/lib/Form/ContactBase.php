<?php
/**
 * Base class for forms dealing with a contact.
 *
 * @package Turba
 */
abstract class Turba_Form_ContactBase extends Horde_Form
{
    /**
     * Set up the Horde_Form fields for $contact's attributes.
     *
     * @param Turba_Object $contact  The contact
     */
    protected function _addFields(Turba_Object $contact, $useTabs = true)
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
