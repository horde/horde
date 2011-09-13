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
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @since Horde_Kolab_Format 1.1.0
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
     * The parameters required for the parsing operation.
     */
    protected $_required_parameters = array('expected-version');

    /**
     * Basic attributes in any Kolab object
     *
     * @var array
     */
    private $_attributes_basic = array(
        'uid' => 'Horde_Kolab_Format_Xml_Type_Uid',
        'body' => array(
            'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
            'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
            'default' => '',
        ),
        'categories' => array(
            'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
            'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
            'default' => '',
        ),
        'creation-date' => 'Horde_Kolab_Format_Xml_Type_CreationDate',
        'last-modification-date' => 'Horde_Kolab_Format_Xml_Type_ModificationDate',
        'sensitivity' => array(
            'type'    => Horde_Kolab_Format_Xml::TYPE_STRING,
            'value'   => Horde_Kolab_Format_Xml::VALUE_DEFAULT,
            'default' => 'public',
        ),
        'inline-attachment' => array(
            'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
            'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type'  => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
        ),
        'link-attachment' => array(
            'type'    => Horde_Kolab_Format_Xml::TYPE_MULTIPLE,
            'value'   => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            'array'   => array(
                'type'  => Horde_Kolab_Format_Xml::TYPE_STRING,
                'value' => Horde_Kolab_Format_Xml::VALUE_MAYBE_MISSING,
            ),
        ),
        'product-id' => 'Horde_Kolab_Format_Xml_Type_ProductId',
    );

    /**
     * Load the node value from the Kolab object.
     *
     * @param string  $name        The name of the the attribute
     *                             to be fetched.
     * @param array   &$attributes The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node to be loaded.
     * @param array   $params      The parameters for this parse operation.
     *
     * @return DOMNode|boolean The named DOMNode or false if no node value was
     *                         found.
     */
    public function load($name, &$attributes, $parent_node, $params = array())
    {
        $this->checkParams($params, $name);
        $this->_getHelper($parent_node, $params);
        if (!($root = $params['helper']->findNode('/' . $name))) {
            throw new Horde_Kolab_Format_Exception_InvalidRoot(
                sprintf('Missing root node "%s"!', $name)
            );
        }
        $attributes['_format-version'] = $root->getAttribute('version');
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
        parent::load($name, $attributes, $parent_node, $params);
        return $root;
    }

    /**
     * Update the specified attribute.
     *
     * @param string  $name        The name of the the attribute
     *                             to be updated.
     * @param array   $attributes  The data array that holds all
     *                             attribute values.
     * @param DOMNode $parent_node The parent node of the node that
     *                             should be updated.
     * @param array   $params      The parameters for this write operation.
     *
     * @return DOMNode|boolean The new/updated child node or false if this
     *                         failed.
     *
     * @throws Horde_Kolab_Format_Exception If converting the data to XML failed.
     */
    public function save($name, $attributes, $parent_node, $params = array())
    {
        $this->checkParams($params, $name);
        $this->_getHelper($parent_node, $params);

        if (!($root = $params['helper']->findNode('/' . $name, $parent_node))) {
            $root = $params['helper']->createNewNode($parent_node, $name);
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
        parent::save($name, $attributes, $parent_node, $params);
        return $root;
    }

    /**
     * Check the parent_node parameter and provide the XML helper in the
     * parameters.
     *
     * @param DOMDocument $parent_node The Document root
     * @param array       &$params     The parameters for this operation.
     *
     * @return NULL
     */
    private function _getHelper($parent_node, &$params)
    {
        if ($parent_node instanceOf DOMDocument) {
            $params['helper'] = $this->createHelper($parent_node);
        } else {
            throw new Horde_Kolab_Format_Exception(
                'The root handler expected a DOMDocument!'
            );
        }
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

        $params['array'] = $this->_attributes_basic;
        if (isset($params['attributes-specific'])) {
            $params['array'] = array_merge(
                $params['array'], $params['attributes-specific']
            );
        }
        if (isset($params['attributes-application'])) {
            $params['array'] = array_merge(
                $params['array'], $params['attributes-application']
            );
        }
        $params['value'] = Horde_Kolab_Format_Xml::VALUE_NOT_EMPTY;
        $params['merge'] = true;
    }
}
