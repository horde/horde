<?php
/**
 * Handles the document root.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Handles the document root.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Root
extends Horde_Kolab_Format_Xml_Type_Composite
{
    /**
     * Indicate which value type is expected.
     *
     * @var int
     */
    protected $value = Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY;

    /**
     * Should the velues be merged into the parent attributes?
     *
     * @var boolean
     */
    protected $merge = true;

    /**
     * Basic attributes in any Kolab object
     *
     * @var array
     */
    private $_attributes_basic = array(
        'uid'                    => 'Horde_Kolab_Format_Xml_Type_Uid',
        'body'                   => 'Horde_Kolab_Format_Xml_Type_String_Empty',
        'categories'             => 'Horde_Kolab_Format_Xml_Type_String_Empty_List',
        'creation-date'          => 'Horde_Kolab_Format_Xml_Type_CreationDate',
        'last-modification-date' => 'Horde_Kolab_Format_Xml_Type_ModificationDate',
        'sensitivity'            => 'Horde_Kolab_Format_Xml_Type_Sensitivity',
        'inline-attachment'      => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
        'link-attachment'        => 'Horde_Kolab_Format_Xml_Type_Multiple_String',
        'product-id'             => 'Horde_Kolab_Format_Xml_Type_ProductId',
    );

    /**
     * Load the node value from the Kolab object.
     *
     * @param string                        $name        The name of the the
     *                                                   attribute to be fetched.
     * @param array                         &$attributes The data array that
     *                                                   holds all attribute
     *                                                   values.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node to be loaded.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      Additiona parameters for
     *                                                   this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load(
        $name,
        &$attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        if (!($root = $helper->findNode('/' . $name))) {
            throw new Horde_Kolab_Format_Exception_InvalidRoot(
                sprintf('Missing root node "%s"!', $name)
            );
        }
        $attributes['_format-version'] = $root->getAttribute('version');
        $attributes['_api-version'] = $params['api-version'];
        if (!$this->isRelaxed($params)) {
            if (version_compare($params['expected-version'], $attributes['_format-version']) < 0) {
                throw new Horde_Kolab_Format_Exception_InvalidRoot(
                    sprintf(
                        'Not attempting to read higher root version of %s with our version %s!',
                        $attributes['_format-version'],
                        $params['expected-version']
                    )
                );
            }
        }
        $this->_prepareCompositeParameters(
            $params, $attributes['_format-version']
        );
        parent::load($name, $attributes, $parent_node, $helper, $params);
        return $root;
    }

    /**
     * Update the specified attribute.
     *
     * @param string                        $name        The name of the the
     *                                                   attribute to be updated.
     * @param array                         $attributes  The data array that holds
     *                                                   all attribute values.
     * @param DOMNode                       $parent_node The parent node of the
     *                                                   node that should be
     *                                                   updated.
     * @param Horde_Kolab_Format_Xml_Helper $helper      A XML helper instance.
     * @param array                         $params      Additional parameters
     *                                                   for this write operation.
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save(
        $name,
        $attributes,
        $parent_node,
        Horde_Kolab_Format_Xml_Helper $helper,
        $params = array()
    )
    {
        if (!($root = $helper->findNode('/' . $name, $parent_node))) {
            $root = $helper->createNewNode($parent_node, $name);
            $root->setAttribute('version', $params['expected-version']);
        } else {
            if (!$this->isRelaxed($params)) {
                if (version_compare($params['expected-version'], $root->getAttribute('version')) < 0) {
                    throw new Horde_Kolab_Format_Exception_InvalidRoot(
                        sprintf(
                            'Not attempting to overwrite higher root version of %s with our version %s!',
                            $root->getAttribute('version'),
                            $params['expected-version']
                        )
                    );
                }
            }
            if ($params['expected-version'] != $root->getAttribute('version')) {
                $root->setAttribute('version', $params['expected-version']);
            }
        }
        $this->_prepareCompositeParameters(
            $params, $params['expected-version']
        );
        parent::save($name, $attributes, $parent_node, $helper, $params);
        return $root;
    }

    /**
     * Prepare the parameters for the parent composite handler.
     *
     * @param array  &$params The parameters for this operation.
     * @param string $version The format version of the document.
     *
     * @return NULL
     */
    private function _prepareCompositeParameters(&$params, $version)
    {
        $params['format-version'] = $version;

        $this->elements = $this->_attributes_basic;
        if (isset($params['attributes-specific'])) {
            $this->elements = array_merge(
                $this->elements, $params['attributes-specific']
            );
        }
        if (isset($params['attributes-application'])) {
            $this->elements = array_merge(
                $this->elements, $params['attributes-application']
            );
        }
    }
}
