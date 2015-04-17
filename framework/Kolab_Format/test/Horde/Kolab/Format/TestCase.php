<?php
/**
 * Basic test case.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Basic test case.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Format
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_TestCase
extends PHPUnit_Framework_TestCase
{
    private $_factory;

    protected function getFactory()
    {
        if ($this->_factory === null) {
            $this->_factory = new Horde_Kolab_Format_Factory();
        }
        return $this->_factory;
    }

    protected function removeLastModification($text)
    {
        return preg_replace(
            '#<last-modification-date>.*</last-modification-date>#',
            '',
            $text
        );
    }

    protected function getXmlType(
        $type,
        $previous = null,
        $kolab_type = 'kolab',
        $version = '1.0'
    )
    {
        $factory = new Horde_Kolab_Format_Factory();
        $doc = new DOMDocument('1.0', 'UTF-8');
        if ($previous !== null) {
            $doc->loadXML($previous);
            $helper = $factory->createXmlHelper($doc);
            $root_node = $helper->findNode('/' . $kolab_type);
        } else {
            $helper = $factory->createXmlHelper($doc);
            $root_node = $helper->createNewNode($doc, $kolab_type);
            $root_node->setAttribute('version', $version);
        }
        $type = $factory->createXmlType($type);
        return array($helper, $root_node, $type);
    }

    protected function load($previous, $params = array())
    {
        list($params, $root_node, $type, $helper) = $this->getTestType(
            $previous, $params
        );
        $attributes = array();
        $this->_load($type, $attributes, $root_node, $helper, $params);
        return $attributes;
    }

    public function loadWithClass($class, $previous = null, $params = array())
    {
        list($helper, $root_node, $type) = $this->getXmlType($class, $previous);
        $attributes = array();
        $this->_load($type, $attributes, $root_node, $helper, $params);
        return $attributes;
    }

    private function _load($type, &$attributes, $root_node, $helper, $params)
    {
        $type->load(
            $this->getElement($params),
            $attributes,
            $root_node,
            $helper,
            $params
        );
    }

    protected function saveToXml(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($params, $root_node, $type, $helper) = $this->getTestType(
            $previous, $params
        );
        $type->save(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
        return (string)$helper;
    }

    protected function saveToReturn(
        $previous = null, $attributes = array(), $params = array()
    )
    {
        list($params, $root_node, $type, $helper) = $this->getTestType(
            $previous, $params
        );
        return $type->save(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
    }

    protected function saveWithClass($class, $previous = null, $params = array(), $attributes = array())
    {
        list($helper, $root_node, $type) = $this->getXmlType($class, $previous);
        return $type->save(
            $this->getElement($params), $attributes, $root_node, $helper, $params
        );
    }

    protected function getTestType($previous, &$params)
    {
        if (isset($params['kolab_type'])) {
            $kolab_type = $params['kolab_type'];
            unset($params['kolab_type']);
        } else {
            $kolab_type = 'kolab';
        }
        if (isset($params['version'])) {
            $version = $params['version'];
            unset($params['version']);
        } else {
            $version = '1.0';
        }
        list($helper, $root_node, $type) = $this->getXmlType(
            $this->getTypeClass(),
            $previous,
            $kolab_type,
            $version
        );
        return array($params, $root_node, $type, $helper);
    }

    protected function getElement(&$params)
    {
        if (isset($params['element'])) {
            $element = $params['element'];
            unset($params['element']);
            return $element;
        } else {
            $elements = explode('_', $this->getTypeClass());
            $element = Horde_String::lower(array_pop($elements));
            return $element;
        }
    }

    protected function getTypeClass()
    {
        throw new Exception('Override!');
    }
}
