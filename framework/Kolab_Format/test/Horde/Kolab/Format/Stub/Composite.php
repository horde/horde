<?php

class Horde_Kolab_Format_Stub_Composite
extends Horde_Kolab_Format_Xml_Type_Composite
{
    public function load(
        $name,
        &$attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        if (isset($params['array'])) {
            $this->elements = $params['array'];
            unset($params['array']);
        }
        if (isset($params['value'])) {
            $this->value = $params['value'];
            unset($params['value']);
        }
        if (isset($params['default'])) {
            $this->default = $params['default'];
            unset($params['default']);
        }
        return parent::load($name, $attributes, $parent_node, $helper, $params);
    }

    public function save(
        $name,
        $attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        if (isset($params['array'])) {
            $this->elements = $params['array'];
            unset($params['array']);
        }
        if (isset($params['value'])) {
            $this->value = $params['value'];
            unset($params['value']);
        }
        if (isset($params['default'])) {
            $this->default = $params['default'];
            unset($params['default']);
        }
        return parent::save($name, $attributes, $parent_node, $helper, $params);
    }
}