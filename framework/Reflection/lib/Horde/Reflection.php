<?php
/**
 * The Horde_Reflection class provides reflection methods, e.g. to generate
 * method documentation.
 *
 * Based on the PEAR XML_RPC2_Server_Method class by Sergio Carvalho
 *
 * Copyright 2004-2006 Sergio Gonalves Carvalho
 *                     (<sergio.carvalho@portugalmail.com>)
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Sergio Carvalho <sergio.carvalho@portugalmail.com>
 * @author  Duck <duck@obala.net>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Reflection
 */
abstract class Horde_Reflection {

    /**
     * Method signature parameters.
     *
     * @var array
     */
    protected $_parameters;

    /**
     * Method signature return type.
     *
     * @var string
     */
    protected $_returns;

    /**
     * Method help, for introspection.
     *
     * @var string
     */
    protected $_help;

    /**
     * Number of required parameters.
     *
     * @var integer
     */
    protected $_numberOfRequiredParameters;

    /**
     * External method name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Constructor.
     *
     * @param ReflectionMethod $method  The PHP method to introspect.
     */
    public function __construct(ReflectionFunction $method)
    {
        $docs = $method->getDocComment();
        $docs = explode("\n", $docs);

        $parameters = array();
        $returns = 'mixed';
        $shortdesc = '';
        $paramcount = -1;
        $this->_name = $method->getName();

        // Extract info from docblock.
        $paramDocs = array();
        foreach ($docs as $i => $doc) {
            $doc = trim($doc, " \r\t/*");
            if (strlen($doc) && strpos($doc, '@') !== 0) {
                if ($shortdesc) {
                    $shortdesc .= "\n";
                }
                $shortdesc .= $doc;
                continue;
            }
            if (strpos($doc, '@param') === 0) {
                // Save doctag for usage later when filling parameters.
                $paramDocs[] = $doc;
            }

            if (strpos($doc, '@return') === 0) {
                $param = preg_split("/\s+/", $doc);
                if (isset($param[1])) {
                    $param = $param[1];
                    $returns = $param;
                }
            }
        }

        // We don't use isOptional() because of bugs in the reflection API.
        $this->_numberOfRequiredParameters = $method->getNumberOfRequiredParameters();
        // Fill in info for each method parameter.
        foreach ($method->getParameters() as $parameterIndex => $parameter) {
            // Parameter defaults.
            $newParameter = array('type' => 'mixed');

            // Attempt to extract type and doc from docblock.
            if (array_key_exists($parameterIndex, $paramDocs) &&
                preg_match('/@param\s+(\S+)(\s+(.+))/',
                           $paramDocs[$parameterIndex],
                           $matches)) {
                if (strpos($matches[1], '|')) {
                    $newParameter['type'] = self::_limitPHPType(explode('|', $matches[1]));
                } else {
                    $newParameter['type'] = self::_limitPHPType($matches[1]);
                }
                $tmp = '$' . $parameter->getName() . ' ';
                if (strpos($matches[2], '$' . $tmp) === 0) {
                    $newParameter['doc'] = $matches[2];
                } else {
                    // The phpdoc comment is something like "@param string
                    // $param description of param". Let's keep only
                    // "description of param" as documentation (remove
                    // $param).
                    $newParameter['doc'] = substr($matches[2], strlen($tmp));
                }
            }

            $parameters[$parameter->getName()] = $newParameter;
        }

        $this->_parameters = $parameters;
        $this->_returns  = $returns;
        $this->_help = $shortdesc;
    }

    /**
     * Returns a complete description of the method.
     *
     * @return string  The method documentation.
     */
    abstract public function autoDocument();

    /**
     * Converts types from phpdoc comments (and limit to xmlrpc available
     * types) to php type names.
     *
     * @var string|array $type  One or multiple phpdoc comment type(s).
     *
     * @return string|array  The standardized php type(s).
     */
    protected static function _limitPHPType($type)
    {
        $convertArray = array(
                'int' => 'integer',
                'i4' => 'integer',
                'integer' => 'integer',
                'string' => 'string',
                'str' => 'string',
                'char' => 'string',
                'bool' => 'boolean',
                'boolean' => 'boolean',
                'array' => 'array',
                'float' => 'double',
                'double' => 'double',
                'array' => 'array',
                'struct' => 'array',
                'assoc' => 'array',
                'structure' => 'array',
                'datetime' => 'mixed',
                'datetime.iso8601' => 'mixed',
                'iso8601' => 'mixed',
                'base64' => 'string'
            );


        if (is_array($type)) {
            $types = array();
            foreach ($type as $tmp) {
                $tmp = Horde_String::lower($tmp);
                if (isset($convertArray[$tmp])) {
                    $types[] = $convertArray[$tmp];
                } else {
                    $types[] = 'mixes';
                }
            }
            return $types;
        } else {
            $tmp = Horde_String::lower($type);
            if (isset($convertArray[$tmp])) {
                return $convertArray[$tmp];
            }
        }

        return 'mixed';
    }

    /**
     * Attempts to return a concrete Horde_Document instance based on $driver.
     *
     * @param string $function  The method to document.
     * @param string $driver    The type of the concrete Horde_Document
     *                          subclass to return. The class name is based on
     *                          the driver.  The code is dynamically included.
     *
     * @return Horde_Document  The newly created concrete Horde_Document
     *                         instance, or false on an error.
     */
    public static function factory($function, $driver = 'Html')
    {
        $class = 'Horde_Reflection_' . $driver;
        if (!class_exists($class)) {
            include dirname(__FILE__) . '/Reflection/' . $driver . '.php';
        }
        if (class_exists($class)) {
            return new $class($function);
        } else {
            return false;
        }
    }

}
