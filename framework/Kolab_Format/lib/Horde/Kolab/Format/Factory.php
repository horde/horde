<?php
/**
 * A factory for generating Kolab format handlers.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * A factory for generating Kolab format handlers.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Factory
{
    /**
     * Parameters for the parser construction.
     *
     * @var array
     */
    private $_params;

    /**
     * Collect xml type instances already created.
     *
     * @var array
     */
    private $_xml_type_instances;

    /**
     * Constructor.
     *
     * @param array $params Additional parameters for the creation of parsers.
     */   
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Generates a handler for a specific Kolab object type.
     *
     * @param string $format The format that the handler should work with.
     * @param string $type   The object type that should be handled.
     * @param array  $params Additional parameters.
     * <pre>
     * 'version' - The format version.
     * </pre>
     *
     * @return Horde_Kolab_Format The handler.
     *
     * @throws Horde_Kolab_Format_Exception If the specified handler does not
     *                                      exist.
     */
    public function create($format = 'Xml', $type = '', array $params = array())
    {
        switch ($type) {
        case 'h-ledger':
            $type_class = 'Envelope';
            break;
        default:
            $type_class = ucfirst(strtolower(str_replace('-', '', $type)));
            break;
        }
        $parser = ucfirst(strtolower($format));
        $class = basename('Horde_Kolab_Format_' . $parser . '_' . $type_class);

        $params = array_merge($this->_params, $params);

        if (class_exists($class)) {
            switch ($parser) {
            case 'Xml':
                $instance = new $class($this->createXmlParser(), $this, $params);
                break;
            default:
                throw new Horde_Kolab_Format_Exception(
                    sprintf(
                        'Failed to initialize the specified parser (Parser type %s does not exist)!',
                        $parser
                    )
                );
            }
        } else {
            throw new Horde_Kolab_Format_Exception(
                sprintf(
                    'Failed to load the specified Kolab Format handler (Class %s does not exist)!',
                    $class
                )
            );
        }
        if (!empty($params['memlog'])) {
            if (!class_exists('Horde_Support_Memory')) {
                throw new Horde_Kolab_Format_Exception('The Horde_Support package seems to be missing (Class Horde_Support_Memory is missing)!');
            }
            $instance = new Horde_Kolab_Format_Decorator_Memory(
                $instance,
                new Horde_Support_Memory(),
                $params['memlog']
            );
        }
        if (!empty($params['timelog'])) {
            if (!class_exists('Horde_Support_Timer')) {
                throw new Horde_Kolab_Format_Exception('The Horde_Support package seems to be missing (Class Horde_Support_Timer is missing)!');
            }
            $instance = new Horde_Kolab_Format_Decorator_Timed(
                $instance,
                new Horde_Support_Timer(),
                $params['timelog']
            );
        }
        return $instance;
    }

    /**
     * Generates a XML parser.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @return Horde_Kolab_Format_Xml_Parser The parser.
     */
    public function createXmlParser()
    {
        return new Horde_Kolab_Format_Xml_Parser(
            new DOMDocument('1.0', 'UTF-8')
        );
    }

    /**
     * Generates a XML helper instance.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @param DOMDocument $xmldoc The XML document the helper works with.
     *
     * @return Horde_Kolab_Format_Xml_Helper The helper utility.
     */
    public function createXmlHelper(DOMDocument $xmldoc)
    {
        return new Horde_Kolab_Format_Xml_Helper($xmldoc);
    }

    /**
     * Generates a XML type that deals with XML data modifications.
     *
     * @since Horde_Kolab_Format 1.1.0
     *
     * @param string      $type   The value type.
     * @param array       $params Additional parameters.
     *
     * @return Horde_Kolab_Format_Xml_Type The type.
     *
     * @throws Horde_Kolab_Format_Exception If the specified type does not
     *                                      exist.
     */
    public function createXmlType($type, $params = array())
    {
        if (isset($params['api-version'])) {
            $class = $type . '_V' . $params['api-version'];
        } else {
            $class = $type;
        }
        if (class_exists($class)) {
            return new $class($this);
        } else if (class_exists($type)) {
            return new $type($this);
        } else {
            throw new Horde_Kolab_Format_Exception(
                sprintf('XML type %s not supported!', $type)
            );
        }
    }
}
