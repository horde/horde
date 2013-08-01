<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class Koward_Form_Object extends Horde_Form {

    /**
     * The link to the application driver.
     *
     * @var Koward_Koward
     */
    protected $koward;

    public function __construct(&$vars, &$object, $params = array())
    {
        $this->koward = &Koward::singleton();

        $this->object = &$object;

        parent::__construct($vars);

        $type = false;

        if (empty($this->object)) {
            $title = _("Add Object");
            $this->setButtons(_("Add"));

            foreach ($this->koward->objects as $key => $config) {
                if (!$this->koward->hasAccess('object/add/' . $key, Koward::PERM_EDIT)) {
                    continue;
                }
                $options[$key] = $config['label'];
            }
            
            $v = &$this->addVariable(_("Choose an object type"), 'type', 'enum', true, false, null, array($options, true));
            $action = Horde_Form_Action::factory('submit');
            $v->setAction($action);
            $v->setOption('trackchange', true);
            if (is_null($vars->get('formname')) &&
                $vars->get($v->getVarName()) != $vars->get('__old_' . $v->getVarName())) {
                $this->koward->notification->push(sprintf(_("Selected object type \"%s\"."), $object_conf[$vars->get('type')]['label']), 'horde.message');
            }

            $type = $vars->get('type');
        } else {
            $title = _("Edit Object");
            $type = $this->koward->getType($this->object);
            if (empty($type)) {
                throw new Koward_Exception('Undefined object class!');
            }
            if (!$this->isSubmitted()) {
                $vars->set('type', $type);
                $keys = array_keys($this->_getFields($this->koward->objects[$type]));
                $vars->set('object', $this->object->toHash($keys));
                $this->setButtons(true);
            }
        }

        if (isset($params['title'])) {
            $title = $params['title'];
        }

        $this->setTitle($title);

        if (!empty($type)) {
            $this->_addFields($this->koward->objects[$type]);
        }
    }

    /**
     * Get the fields for an object type
     */
    public function getTypeFields($type)
    {
        return $this->_getFields($this->koward->objects[$type]);
    }
    /**
     * Get the fields for a configuration array.
     */
    private function _getFields($config)
    {
        if (isset($config['attributes']['fields']) && !empty($config['attributes']['override'])) {
            return $config['attributes']['fields'];
        } else {
            list($attributes, $attribute_map) = $this->koward->getServer()->getAttributes($config['class']);

            if (isset($this->koward->visible['show'])) {
                $akeys = $this->koward->visible['show'];
            } else if (isset($config['attributes']['show'])) {
                $akeys = $config['attributes']['show'];
            } else {
                $akeys = array_keys($attributes);
                if (isset($config['attributes']['hide'])) {
                    $akeys = array_diff($akeys, $config['attributes']['hide']);
                }
            }

            $form_attributes = array();

            foreach ($akeys as $key) {
                if ((isset($this->koward->visible['hide'])
                     && in_array($key, $this->koward->visible['hide']))
                    || (isset($config['attributes']['hide'])
                        && in_array($key, $config['attributes']['hide']))) {
                    continue;
                }

                if (isset($config['attributes']['type'][$key])) {
                    $type = $config['attributes']['type'][$key];
                } else if (isset($attributes[$key]['syntax'])) {
                    list($syntax, $length) = explode('{', $attributes[$key]['syntax'], 2);
                    switch ($syntax) {
                    case '1.3.6.1.4.1.1466.115.121.1.22':
                    case '1.3.6.1.4.1.1466.115.121.1.50':
                        $type = 'phone';
                        break;
                    case '1.3.6.1.4.1.1466.115.121.1.28':
                        $type = 'image';
                        break;
                    default:
                        $type = 'text';
                        break;
                    }
                } else {
                    $type = 'text';
                }

                $locked = in_array($key, $attribute_map['locked']) && !empty($this->object);
                if (!$locked) {
                    $required = in_array($key, $attribute_map['required'])  && empty($this->object);
                }

                $form_attributes[$key] = array(
                    'type' => $type,
                    'required' => $required,
                    'readonly' => $locked,
                    'params' => array('regex' => '', 'size' => 40, 'maxlength' => 255)
                );
                if (isset($config['attributes']['order'][$key])) {
                    $form_attributes[$key]['order'] = $config['attributes']['order'][$key];
                } else if (isset($this->koward->order[$key])) {
                    $form_attributes[$key]['order'] = $this->koward->order[$key];
                } else {
                    $form_attributes[$key]['order'] = -1;
                }
                if (isset($config['attributes']['labels'][$key])) {
                    $form_attributes[$key]['label'] = $config['attributes']['labels'][$key];
                } else if (isset($this->koward->labels[$key])) {
                    $form_attributes[$key]['label'] = $this->koward->labels[$key];
                } else {
                    $form_attributes[$key]['label'] = $key;
                }
                if (isset($this->koward->attributes[$key])) {
                    $form_attributes[$key] = array_merge($form_attributes[$key],
                                                         $this->koward->attributes[$key]);
                }
                if (isset($config['attributes']['fields'][$key])) {
                    $form_attributes[$key] = array_merge($form_attributes[$key],
                                                         $config['attributes']['fields'][$key]);
                }
            }
            uasort($form_attributes, array($this, '_sortFields'));
            return $form_attributes;
        }
        return array();
    }

    /**
     * Sort fields for an object type
     */
    function _sortFields($a, $b)
    {
        if ($a['order'] == -1) {
            return 1;
        }
        if ($b['order'] == -1) {
            return -1;
        }
        if ($a['order'] == $b['order']) {
            return 0;
        }
        return ($a['order'] < $b['order']) ? -1 : 1;
    }

    /**
     * Set up the Horde_Form fields for the attributes of this object type.
     */
    function _addFields($config)
    {
        // Now run through and add the form variables.
        $fields = $this->_getFields($config);
        $tabs   = isset($config['tabs']) ? $config['tabs'] : array('' => $fields);

        foreach ($tabs as $tab => $tab_fields) {
            if (!empty($tab)) {
                $this->setSection($tab, $tab);
            }
            foreach ($tab_fields as $key => $field) {
                if (!in_array($key, array_keys($fields))
                    //                    || !isset($this->koward->attributes[$key])
                ) {
                    continue;
                }
                $attribute = $field;
                //                $attribute = $this->koward->attributes[$key];
                $params = isset($attribute['params']) ? $attribute['params'] : array();
                $desc = isset($attribute['desc']) ? $attribute['desc'] : null;

                $readonly = isset($attribute['readonly']) ? $attribute['readonly'] : null;
                $v = &$this->addVariable($attribute['label'], 'object[' . $key . ']', $attribute['type'], $attribute['required'], $readonly, $desc, $params);
            }

            if (isset($attribute['default'])) {
                $v->setDefault($attribute['default']);
            }
        }
    }

    function &execute()
    {
        $this->getInfo($this->_vars, $info);
        if (isset($info['object'])) {
            if (empty($this->object)) {
                if (isset($info['type'])) {
                    if (isset($this->koward->objects[$info['type']]['class'])) {
                        $class = $this->koward->objects[$info['type']]['class'];
                    } else {
                        throw new Koward_Exception(sprintf('Invalid type \"%s\" specified!',
                                                           $info['type']));
                    }
                    $object = $this->koward->getServer()->add(array_merge(array('type' => $class),
                                                                 $info['object']));
                    $this->koward->notification->push(_("Successfully added the object."),
                                                      'horde.message');
                    return $object;
                }
            } else {
                $this->object->save($info['object']);
                $this->koward->notification->push(_("Successfully updated the object."),
                                                 'horde.message');
                return $this->object;
            }
        }
    }
}
