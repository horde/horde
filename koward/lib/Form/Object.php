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
        $this->koward = &Koward_Koward::singleton();

        $this->object = &$object;

        parent::Horde_Form($vars);

        if (empty($this->object)) {
            $title = _("Add Object");
            $this->setButtons(_("Add"));

            foreach ($this->koward->objects as $key => $config) {
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
        } else {
            $title = _("Edit Object");
            $type = get_class($this->object);
            if (!$this->isSubmitted()) {
                $vars->set('type', $type);
                $keys = array_keys($this->koward->objects[$type]['attributes']);
                $vars->set('object', $this->object->toHash($keys));
                $this->setButtons(_("Edit"));
            }
        }

        if (isset($params['title'])) {
            $title = $params['title'];
        }

        $this->setTitle($title);

        $type = $vars->get('type');
        if (isset($type)) {
            $this->_addFields($this->koward->objects[$type]);
        }
    }

    /**
     * Set up the Horde_Form fields for the attributes of this object type.
     */
    function _addFields($config)
    {
        // Now run through and add the form variables.
        $fields = isset($config['attributes']) ? $config['attributes'] : array();
        $tabs   = isset($config['tabs']) ? $config['tabs'] : array('' => $fields);

        foreach ($tabs as $tab => $tab_fields) {
            if (!empty($tab)) {
                $this->setSection($tab, $tab);
            }
            foreach ($tab_fields as $key => $field) {
                if (!in_array($key, array_keys($fields)) ||
                    !isset($this->koward->attributes[$key])) {
                    continue;
                }

                $attribute = $this->koward->attributes[$key];
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
                    $object = $this->koward->server->add(array_merge(array('type' => $info['type']),
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
