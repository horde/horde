<?php
/**
 * The Horde_Config:: package provides a framework for managing the
 * configuration of Horde applications, writing conf.php files from
 * conf.xml source files, generating user interfaces, etc.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Config
{
    /**
     * The name of the configured application.
     *
     * @var string
     */
    protected $_app;

    /**
     * The XML tree of the configuration file traversed to an
     * associative array.
     *
     * @var array
     */
    protected $_xmlConfigTree = null;

    /**
     * The content of the generated configuration file.
     *
     * @var string
     */
    protected $_phpConfig;

    /**
     * The content of the old configuration file.
     *
     * @var string
     */
    protected $_oldConfig;

    /**
     * The manual configuration in front of the generated configuration.
     *
     * @var string
     */
    protected $_preConfig;

    /**
     * The manual configuration after the generated configuration.
     *
     * @var string
     */
    protected $_postConfig;

    /**
     * The current $conf array of the configured application.
     *
     * @var array
     */
    protected $_currentConfig = array();

    /**
     * The version tag of the conf.xml file which will be copied into the
     * conf.php file.
     *
     * @var string
     */
    protected $_versionTag = '';

    /**
     * The line marking the begin of the generated configuration.
     *
     * @var string
     */
    protected $_configBegin = "/* CONFIG START. DO NOT CHANGE ANYTHING IN OR AFTER THIS LINE. */\n";

    /**
     * The line marking the end of the generated configuration.
     *
     * @var string
     */
    protected $_configEnd = "/* CONFIG END. DO NOT CHANGE ANYTHING IN OR BEFORE THIS LINE. */\n";

    /**
     * Horde URL to check version information.
     *
     * @var string
     */
    protected $_versionUrl = 'http://www.horde.org/versions.php';

    /**
     * Constructor.
     *
     * @param string $app  The name of the application to be configured.
     */
    public function __construct($app = 'horde')
    {
        $this->_app = $app;
    }

    /**
     * Contact Horde servers and get version information.
     *
     * @return array  Keys are app names, values are arrays with two keys:
     *                'version' and 'url'.
     * @throws Horde_Exception
     * @throws Horde_Http_Client_Exception
     */
    public function checkVersions()
    {
        if (!Horde_Util::extensionExists('SimpleXML')) {
            throw new Horde_Exception('SimpleXML not available.');
        }

        $http = $GLOBALS['injector']->getInstance('Horde_Core_Factory_HttpClient')->create();
        $response = $http->get($this->_versionUrl);
        if ($response->code != 200) {
            throw new Horde_Exception('Unexpected response from server.');
        }

        $xml = new SimpleXMLElement($response->getBody());
        $versions = array();

        foreach ($xml->stable->application as $app) {
            $versions[strval($app['name'])] = array(
                'version' => $app->version,
                'url' => $app->url
            );
        }

        return $versions;
    }

    /**
     * Reads the application's conf.xml file and builds an associative array
     * from its XML tree.
     *
     * @param array $custom_conf  Any settings that shall be included in the
     *                            generated configuration.
     *
     * @return array  An associative array representing the configuration
     *                tree.
     */
    public function readXMLConfig($custom_conf = null)
    {
        if (!is_null($this->_xmlConfigTree) && !$custom_conf) {
            return $this->_xmlConfigTree;
        }

        $path = $GLOBALS['registry']->get('fileroot', $this->_app) . '/config';

        if ($custom_conf) {
            $this->_currentConfig = $custom_conf;
        } else {
            /* Fetch the current conf.php contents. */
            @eval($this->getPHPConfig());
            if (isset($conf)) {
                $this->_currentConfig = $conf;
            }
        }

        /* Load the DOM object. */
        $dom = new DOMDocument();
        $dom->load($path . '/conf.xml');

        /* Check if there is a CVS/Git version tag and store it. */
        $node = $dom->firstChild;
        while (!empty($node)) {
            if (($node->nodeType == XML_COMMENT_NODE) &&
                ($vers_tag = $this->getVersion($node->nodeValue))) {
                $this->_versionTag = $vers_tag . "\n";
                break;
            }
            $node = $node->nextSibling;
        }

        /* Parse the config file. */
        $this->_xmlConfigTree = array();
        $root = $dom->documentElement;
        if ($root->hasChildNodes()) {
            $this->_parseLevel($this->_xmlConfigTree, $root->childNodes, '');
        }

        return $this->_xmlConfigTree;
    }

    /**
     * Get the Horde version string for a config file.
     *
     * @param string $text  The text to parse.
     *
     * @return string  The version string or false if not found.
     */
    public function getVersion($text)
    {
        // Old CVS tag
        if (preg_match('/\$.*?conf\.xml,v .*? .*\$/', $text, $match) ||
            // New Git tag
            preg_match('/\$Id:\s*[0-9a-f]+\s*\$/', $text, $match)) {
            return $match[0];
        }

        return false;
    }

    /**
     * Returns the file content of the current configuration file.
     *
     * @return string  The unparsed configuration file content.
     */
    public function getPHPConfig()
    {
        if (!is_null($this->_oldConfig)) {
            return $this->_oldConfig;
        }

        $path = $GLOBALS['registry']->get('fileroot', $this->_app) . '/config';
        if (file_exists($path . '/conf.php')) {
            $this->_oldConfig = file_get_contents($path . '/conf.php');
            if (!empty($this->_oldConfig)) {
                $this->_oldConfig = preg_replace('/<\?php\n?/', '', $this->_oldConfig);
                $pos = strpos($this->_oldConfig, $this->_configBegin);
                if ($pos !== false) {
                    $this->_preConfig = substr($this->_oldConfig, 0, $pos);
                    $this->_oldConfig = substr($this->_oldConfig, $pos);
                }
                $pos = strpos($this->_oldConfig, $this->_configEnd);
                if ($pos !== false) {
                    $this->_postConfig = substr($this->_oldConfig, $pos + strlen($this->_configEnd));
                    $this->_oldConfig = substr($this->_oldConfig, 0, $pos);
                }
            }
        } else {
            $this->_oldConfig = '';
        }

        return $this->_oldConfig;
    }

    /**
     * Generates the content of the application's configuration file.
     *
     * @param Horde_Variables $formvars  The processed configuration form
     *                                   data.
     * @param array $custom_conf         Any settings that shall be included
     *                                   in the generated configuration.
     *
     * @return string  The content of the generated configuration file.
     */
    public function generatePHPConfig($formvars, $custom_conf = null)
    {
        $this->readXMLConfig($custom_conf);
        $this->getPHPConfig();

        $this->_phpConfig = "<?php\n" . $this->_preConfig . $this->_configBegin;
        if (!empty($this->_versionTag)) {
            $this->_phpConfig .= '// ' . $this->_versionTag;
        }
        $this->_generatePHPConfig($this->_xmlConfigTree, '', $formvars);
        $this->_phpConfig .= $this->_configEnd . $this->_postConfig;

        return $this->_phpConfig;
    }

    /**
     * Generates the configuration file items for a part of the configuration
     * tree.
     *
     * @param array $section             An associative array containing the
     *                                   part of the traversed XML
     *                                   configuration tree that should be
     *                                   processed.
     * @param string $prefix             A configuration prefix determining
     *                                   the current position inside the
     *                                   configuration file. This prefix will
     *                                   be translated to keys of the $conf
     *                                   array in the generated configuration
     *                                   file.
     * @param Horde_Variables $formvars  The processed configuration form
     *                                   data.
     */
    protected function _generatePHPConfig($section, $prefix, $formvars)
    {
        if (!is_array($section)) {
            return;
        }

        foreach ($section as $name => $configitem) {
            $prefixedname = empty($prefix)
                ? $name
                : $prefix . '|' . $name;
            $configname = str_replace('|', '__', $prefixedname);
            $quote = (!isset($configitem['quote']) || $configitem['quote'] !== false);

            if ($configitem == 'placeholder') {
                $this->_phpConfig .= '$conf[\'' . str_replace('|', '\'][\'', $prefix) . "'] = array();\n";
            } elseif (isset($configitem['switch'])) {
                $val = $formvars->getExists($configname, $wasset);
                if (!$wasset) {
                    $val = isset($configitem['default']) ? $configitem['default'] : null;
                }
                if (isset($configitem['switch'][$val])) {
                    $value = $val;
                    if ($quote && $value != 'true' && $value != 'false') {
                        $value = "'" . $value . "'";
                    }
                    $this->_generatePHPConfig($configitem['switch'][$val]['fields'], $prefix, $formvars);
                }
            } elseif (isset($configitem['_type'])) {
                $val = $formvars->getExists($configname, $wasset);
                if (!$wasset &&
                    ((array_key_exists('is_default', $configitem) && $configitem['is_default'])
                     || !array_key_exists('is_default', $configitem))) {

                    $val = isset($configitem['default']) ? $configitem['default'] : null;
                }

                $type = $configitem['_type'];
                switch ($type) {
                case 'multienum':
                    if (is_array($val)) {
                        $encvals = array();
                        foreach ($val as $v) {
                            $encvals[] = $this->_quote($v);
                        }
                        $arrayval = "'" . implode('\', \'', $encvals) . "'";
                        if ($arrayval == "''") {
                            $arrayval = '';
                        }
                    } else {
                        $arrayval = '';
                    }
                    $value = 'array(' . $arrayval . ')';
                    break;

                case 'boolean':
                    if (is_bool($val)) {
                        $value = $val ? 'true' : 'false';
                    } else {
                        $value = ($val == 'on') ? 'true' : 'false';
                    }
                    break;

                case 'stringlist':
                    $values = explode(',', $val);
                    if (!is_array($values)) {
                        $value = "array('" . $this->_quote(trim($values)) . "')";
                    } else {
                        $encvals = array();
                        foreach ($values as $v) {
                            $encvals[] = $this->_quote(trim($v));
                        }
                        $arrayval = "'" . implode('\', \'', $encvals) . "'";
                        if ($arrayval == "''") {
                            $arrayval = '';
                        }
                        $value = 'array(' . $arrayval . ')';
                    }
                    break;

                case 'int':
                    if ($val !== '') {
                        $value = (int)$val;
                    }
                    break;

                case 'octal':
                    $value = sprintf('0%o', octdec($val));
                    break;

                case 'header':
                case 'description':
                    break;

                default:
                    if ($val != '') {
                        $value = $val;
                        if ($quote && $value != 'true' && $value != 'false') {
                            $value = "'" . $this->_quote($value) . "'";
                        }
                    }
                    break;
                }
            } else {
                $this->_generatePHPConfig($configitem, $prefixedname, $formvars);
            }

            if (isset($value)) {
                $this->_phpConfig .= '$conf[\'' . str_replace('__', '\'][\'', $configname) . '\'] = ' . $value . ";\n";
            }
            unset($value);
        }
    }

    /**
     * Parses one level of the configuration XML tree into the associative
     * array containing the traversed configuration tree.
     *
     * @param array &$conf           The already existing array where the
     *                               processed XML tree portion should be
     *                               appended to.
     * @param DOMNodeList $children  The XML nodes of the level that should
     *                               be parsed.
     * @param string $ctx            A string representing the current
     *                               position (context prefix) inside the
     *                               configuration XML file.
     */
    protected function _parseLevel(&$conf, $children, $ctx)
    {
        foreach ($children as $node) {
            if ($node->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $name = $node->getAttribute('name');
            $desc = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($node->getAttribute('desc'), 'linkurls');
            $required = !($node->getAttribute('required') == 'false');
            $quote = !($node->getAttribute('quote') == 'false');

            $curctx = empty($ctx)
                ? $name
                : $ctx . '|' . $name;

            switch ($node->tagName) {
            case 'configdescription':
                if (empty($name)) {
                    $name = uniqid(mt_rand());
                }

                $conf[$name] = array(
                    '_type' => 'description',
                    'desc' => $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->_default($curctx, $this->_getNodeOnlyText($node)), 'linkurls')
                );
                break;

            case 'configheader':
                if (empty($name)) {
                    $name = uniqid(mt_rand());
                }

                $conf[$name] = array(
                    '_type' => 'header',
                    'desc' => $this->_default($curctx, $this->_getNodeOnlyText($node))
                );
                break;

            case 'configswitch':
                $values = $this->_getSwitchValues($node, $ctx);
                list($default, $isDefault) = $quote
                    ? $this->__default($curctx, $this->_getNodeOnlyText($node))
                    : $this->__defaultRaw($curctx, $this->_getNodeOnlyText($node));

                if ($default === '') {
                    $default = key($values);
                }

                if (is_bool($default)) {
                    $default = $default ? 'true' : 'false';
                }

                $conf[$name] = array(
                    'desc' => $desc,
                    'switch' => $values,
                    'default' => $default,
                    'is_default' => $isDefault
                );
                break;

            case 'configenum':
                $values = $this->_getEnumValues($node);
                list($default, $isDefault) = $quote
                    ? $this->__default($curctx, $this->_getNodeOnlyText($node))
                    : $this->__defaultRaw($curctx, $this->_getNodeOnlyText($node));

                if ($default === '') {
                    $default = key($values);
                }

                if (is_bool($default)) {
                    $default = $default ? 'true' : 'false';
                }

                $conf[$name] = array(
                    '_type' => 'enum',
                    'required' => $required,
                    'quote' => $quote,
                    'values' => $values,
                    'desc' => $desc,
                    'default' => $default,
                    'is_default' => $isDefault
                );
                break;

            case 'configlist':
                list($default, $isDefault) = $this->__default($curctx, null);

                if (is_null($default)) {
                    $default = $this->_getNodeOnlyText($node);
                } elseif (is_array($default)) {
                    $default = implode(', ', $default);
                }

                $conf[$name] = array(
                    '_type' => 'stringlist',
                    'required' => $required,
                    'desc' => $desc,
                    'default' => $default,
                    'is_default' => $isDefault
                );
                break;

            case 'configmultienum':
                $values = $this->_getEnumValues($node);
                list($default, $isDefault) = $this->__default($curctx, explode(',', $this->_getNodeOnlyText($node)));

                $conf[$name] = array(
                    '_type' => 'multienum',
                    'required' => $required,
                    'values' => $values,
                    'desc' => $desc,
                    'default' => Horde_Array::valuesToKeys($default),
                    'is_default' => $isDefault
                );
                break;

            case 'configpassword':
                $conf[$name] = array(
                    '_type' => 'password',
                    'required' => $required,
                    'desc' => $desc,
                    'default' => $this->_default($curctx, $this->_getNodeOnlyText($node)),
                    'is_default' => $this->_isDefault($curctx, $this->_getNodeOnlyText($node))
                );
                break;

            case 'configstring':
                $conf[$name] = array(
                    '_type' => 'text',
                    'required' => $required,
                    'desc' => $desc,
                    'default' => $this->_default($curctx, $this->_getNodeOnlyText($node)),
                    'is_default' => $this->_isDefault($curctx, $this->_getNodeOnlyText($node))
                );

                if ($conf[$name]['default'] === false) {
                    $conf[$name]['default'] = 'false';
                } elseif ($conf[$name]['default'] === true) {
                    $conf[$name]['default'] = 'true';
                }
                break;

            case 'configboolean':
                $default = $this->_getNodeOnlyText($node);
                $default = !(empty($default) || $default === 'false');

                $conf[$name] = array(
                    '_type' => 'boolean',
                    'required' => $required,
                    'desc' => $desc,
                    'default' => $this->_default($curctx, $default),
                    'is_default' => $this->_isDefault($curctx, $default)
                );
                break;

            case 'configinteger':
                $values = $this->_getEnumValues($node);

                $conf[$name] = array(
                    '_type' => 'int',
                    'required' => $required,
                    'values' => $values,
                    'desc' => $desc,
                    'default' => $this->_default($curctx, $this->_getNodeOnlyText($node)),
                    'is_default' => $this->_isDefault($curctx, $this->_getNodeOnlyText($node))
                );

                if ($node->getAttribute('octal') == 'true' &&
                    $conf[$name]['default'] != '') {
                    $conf[$name]['_type'] = 'octal';
                    $conf[$name]['default'] = sprintf('0%o', $this->_default($curctx, octdec($this->_getNodeOnlyText($node))));
                }
                break;

            case 'configldap':
                $conf[$node->getAttribute('switchname')] = $this->_configLDAP($ctx, $node);
                break;

            case 'configphp':
                $conf[$name] = array(
                    '_type' => 'php',
                    'required' => $required,
                    'quote' => false,
                    'desc' => $desc,
                    'default' => $this->_defaultRaw($curctx, $this->_getNodeOnlyText($node)),
                    'is_default' => $this->_isDefaultRaw($curctx, $this->_getNodeOnlyText($node))
                );
                break;

            case 'configsecret':
                $conf[$name] = array(
                    '_type' => 'text',
                    'required' => true,
                    'desc' => $desc,
                    'default' => $this->_default($curctx, strval(new Horde_Support_Uuid())),
                    'is_default' => $this->_isDefault($curctx, $this->_getNodeOnlyText($node))
                );
                break;

            case 'configsql':
                $conf[$node->getAttribute('switchname')] = $this->_configSQL($ctx, $node);
                break;

            case 'configvfs':
                $conf[$node->getAttribute('switchname')] = $this->_configVFS($ctx, $node);
                break;

            case 'configsection':
                $conf[$name] = array();
                $cur = &$conf[$name];
                if ($node->hasChildNodes()) {
                    $this->_parseLevel($cur, $node->childNodes, $curctx);
                }
                break;

            case 'configtab':
                $key = uniqid(mt_rand());

                $conf[$key] = array(
                    'tab' => $name,
                    'desc' => $desc
                );

                if ($node->hasChildNodes()) {
                    $this->_parseLevel($conf, $node->childNodes, $ctx);
                }
                break;

            case 'configplaceholder':
                $conf[uniqid(mt_rand())] = 'placeholder';
                break;

            default:
                $conf[$name] = array();
                $cur = &$conf[$name];
                if ($node->hasChildNodes()) {
                    $this->_parseLevel($cur, $node->childNodes, $curctx);
                }
                break;
            }
        }
    }

    /**
     * Returns the configuration tree for an LDAP backend configuration to
     * replace a <configldap> tag.
     * Subnodes will be parsed and added to both the Horde defaults and the
     * Custom configuration parts.
     *
     * @param string $ctx         The context of the <configldap> tag.
     * @param DomNode $node       The DomNode representation of the
     *                            <configldap> tag.
     * @param string $switchname  If DomNode is not set, the value of the
     *                            tag's switchname attribute.
     *
     * @return array  An associative array with the LDAP configuration tree.
     */
    protected function _configLDAP($ctx, $node = null,
                                   $switchname = 'driverconfig')
    {
        $fields = array(
            'hostspec' => array(
                '_type' => 'text',
                'required' => true,
                'desc' => 'LDAP server/hostname',
                'default' => $this->_default($ctx . '|hostspec', '')
            ),

            'port' => array(
                '_type' => 'int',
                'required' => false,
                'desc' => 'Port on which LDAP is listening, if non-standard',
                'default' => $this->_default($ctx . '|port', null)
            ),

            'tls' => array(
                '_type' => 'boolean',
                'required' => false,
                'desc' => 'Use TLS to connect to the server?',
                'default' => $this->_default($ctx . '|tls', false)
            ),

            'version' => array(
                '_type' => 'int',
                'required' => true,
                'quote' => false,
                'desc' => 'LDAP protocol version',
                'switch' => array(
                    '2' => array(
                        'desc' => '2 (deprecated)',
                        'fields' => array()
                    ),
                    '3' => array(
                        'desc' => '3',
                        'fields' => array()
                    )
                ),
                'default' => $this->_default($ctx . '|version', 3)
            ),

            'bindas' => array(
                'desc' => 'Bind to LDAP as which user?',
                'default' => $this->_default($ctx . '|bindas', 'admin'),
                'switch' => array(
                    'anon' => array(
                        'desc' => 'Bind anonymously',
                        'fields' => array()
                    ),
                    'user' => array(
                        'desc' => 'Bind as the currently logged-in user',
                        'fields' => array(
                            'user' => array(
                                'binddn' => array(
                                    '_type' => 'text',
                                    'required' => false,
                                    'desc' => 'DN used to bind for searching the user\'s DN (leave empty for anonymous bind)',
                                    'default' => $this->_default($ctx . '|binddn', '')
                                ),
                                'bindpw' => array(
                                    '_type' => 'text',
                                    'required' => false,
                                    'desc' => 'Password for bind DN',
                                    'default' => $this->_default($ctx . '|bindpw', '')
                                ),
                                'uid' => array(
                                    '_type' => 'text',
                                    'required' => true,
                                    'desc' => 'The username search key (set to samaccountname for AD).',
                                    'default' => $this->_default($ctx . '|user|uid', 'uid')
                                ),
                                'filter_type' => array(
                                    'required' => false,
                                    'desc' => 'How to specify a filter for the user lists.',
                                    'default' => $this->_default($ctx . '|user|filter_type', 'objectclass'),
                                    'switch' => array(
                                        'filter' => array(
                                            'desc' => 'LDAP filter string',
                                            'fields' => array(
                                                'filter' => array(
                                                    '_type' => 'text',
                                                    'required' => true,
                                                    'desc' => 'The LDAP filter string used to search for users.',
                                                    'default' => $this->_default($ctx . '|user|filter', '(objectClass=*)')
                                                ),
                                            ),
                                        ),
                                        'objectclass' => array(
                                            'desc' => 'List of objectClasses',
                                            'fields' => array(
                                                'objectclass' => array(
                                                    '_type' => 'stringlist',
                                                    'required' => true,
                                                    'desc' => 'The objectclass filter used to search for users. Can be a single objectclass or a comma-separated list.',
                                                    'default' => implode(', ', $this->_default($ctx . '|user|objectclass', array('*')))
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'admin' => array(
                        'desc' => 'Bind with administrative/system credentials',
                        'fields' => array(
                            'binddn' => array(
                                '_type' => 'text',
                                'required' => true,
                                'desc' => 'DN used to bind to LDAP',
                                'default' => $this->_default($ctx . '|binddn', '')
                            ),
                            'bindpw' => array(
                                '_type' => 'text',
                                'required' => true,
                                'desc' => 'Password for bind DN',
                                'default' => $this->_default($ctx . '|bindpw', '')
                            )
                        )
                    ),
                )
            ),
        );

        if (isset($node) && $node->getAttribute('excludebind')) {
            $excludes = explode(',', $node->getAttribute('excludebind'));
            foreach ($excludes as $exclude) {
                unset($fields['bindas']['switch'][$exclude]);
            }
        }

        if (isset($node) && $node->getAttribute('baseconfig') == 'true') {
            return array(
                'desc' => 'Use LDAP?',
                'default' => $this->_default($ctx . '|' . $node->getAttribute('switchname'), false),
                'switch' => array(
                    'false' => array(
                        'desc' => 'No',
                        'fields' => array()
                    ),
                    'true' => array(
                        'desc' => 'Yes',
                        'fields' => $fields
                    ),
                )
            );
        }

        $standardFields = array(
            'basedn' => array(
                '_type' => 'text',
                'required' => true,
                'desc' => 'Base DN',
                'default' => $this->_default($ctx . '|basedn', '')
            ),
            'scope' => array(
                '_type' => 'enum',
                'required' => true,
                'desc' => 'Search scope',
                'default' => $this->_default($ctx . '|scope', ''),
                'values' => array(
                    'sub' => 'Subtree search',
                    'one' => 'One level'),
            ),
        );

        list($default, $isDefault) = $this->__default($ctx . '|' . (isset($node) ? $node->getAttribute('switchname') : $switchname), 'horde');
        $config = array(
            'desc' => 'Driver configuration',
            'default' => $default,
            'is_default' => $isDefault,
            'switch' => array(
                'horde' => array(
                    'desc' => 'Horde defaults',
                    'fields' => $standardFields,
                ),
                'custom' => array(
                    'desc' => 'Custom parameters',
                    'fields' => $fields + $standardFields,
                )
            )
        );

        if (isset($node) && $node->hasChildNodes()) {
            $cur = array();
            $this->_parseLevel($cur, $node->childNodes, $ctx);
            $config['switch']['horde']['fields'] = array_merge($config['switch']['horde']['fields'], $cur);
            $config['switch']['custom']['fields'] = array_merge($config['switch']['custom']['fields'], $cur);
        }

        return $config;
    }

    /**
     * Returns the configuration tree for an SQL backend configuration to
     * replace a <configsql> tag.
     * Subnodes will be parsed and added to both the Horde defaults and the
     * Custom configuration parts.
     *
     * @param string $ctx         The context of the <configsql> tag.
     * @param DomNode $node       The DomNode representation of the <configsql>
     *                            tag.
     * @param string $switchname  If DomNode is not set, the value of the
     *                            tag's switchname attribute.
     *
     * @return array  An associative array with the SQL configuration tree.
     */
    protected function _configSQL($ctx, $node = null,
                                  $switchname = 'driverconfig')
    {
        $persistent = array(
            '_type' => 'boolean',
            'required' => false,
            'desc' => 'Request persistent connections?',
            'default' => $this->_default($ctx . '|persistent', false)
        );

        $hostspec = array(
            '_type' => 'text',
            'required' => true,
            'desc' => 'Database server/host',
            'default' => $this->_default($ctx . '|hostspec', '')
        );

        $username = array(
            '_type' => 'text',
            'required' => true,
            'desc' => 'Username to connect to the database as',
            'default' => $this->_default($ctx . '|username', '')
        );

        $password = array(
            '_type' => 'text',
            'required' => false,
            'desc' => 'Password to connect with',
            'default' => $this->_default($ctx . '|password', '')
        );

        $database = array(
            '_type' => 'text',
            'required' => true,
            'desc' => 'Database name to use',
            'default' => $this->_default($ctx . '|database', '')
        );

        $socket = array(
            '_type' => 'text',
            'required' => false,
            'desc' => 'Location of UNIX socket',
            'default' => $this->_default($ctx . '|socket', '')
        );

        $port = array(
            '_type' => 'int',
            'required' => false,
            'desc' => 'Port the DB is running on, if non-standard',
            'default' => $this->_default($ctx . '|port', null)
        );

        $protocol = array(
            'desc' => 'How should we connect to the database?',
            'default' => $this->_default($ctx . '|protocol', 'unix'),
            'switch' => array(
                'unix' => array(
                    'desc' => 'UNIX Sockets',
                    'fields' => array(
                        'socket' => $socket
                    )
                ),
                'tcp' => array(
                    'desc' => 'TCP/IP',
                    'fields' => array(
                        'hostspec' => $hostspec,
                        'port' => $port
                    )
                )
            )
        );

        $mysql_protocol = $protocol;
        $mysql_protocol['switch']['tcp']['fields']['port']['default'] = $this->_default($ctx . '|port', 3306);

        $charset = array(
            '_type' => 'text',
            'required' => true,
            'desc' => 'Internally used charset',
            'default' => $this->_default($ctx . '|charset', 'utf-8')
        );

        $ssl = array(
            '_type' => 'boolean',
            'required' => false,
            'desc' => 'Use SSL to connect to the server?',
            'default' => $this->_default($ctx . '|ssl', false)
        );

        $ca = array(
            '_type' => 'text',
            'required' => false,
            'desc' => 'Certification Authority to use for SSL connections',
            'default' => $this->_default($ctx . '|ca', '')
        );

        $oci8_fields = array(
            'persistent' => $persistent,
            'username' => $username,
            'password' => $password
        );
        if (function_exists('oci_connect')) {
            $oci8_fields['database'] = array(
                '_type' => 'text',
                'required' => true,
                'desc' => 'Database name or Easy Connect parameter',
                'default' => $this->_default($ctx . '|database', 'horde')
            );
        } else {
            $oci8_fields['hostspec'] = array(
                '_type' => 'text',
                'required' => true,
                'desc' => 'Database name or Easy Connect parameter',
                'default' => $this->_default($ctx . '|hostspec', 'horde')
            );
        }
        $oci8_fields['charset'] = $charset;

        $read_hostspec = array(
            '_type' => 'text',
            'required' => true,
            'desc' => 'Read database server/host',
            'default' => $this->_default($ctx . '|read|hostspec', '')
        );

        $read_port = array(
            '_type' => 'int',
            'required' => false,
            'desc' => 'Port the read DB is running on, if non-standard',
            'default' => $this->_default($ctx . '|read|port', null)
        );

        $splitread = array(
            '_type' => 'boolean',
            'required' => false,
            'desc' => 'Split reads to a different server?',
            'default' => $this->_default($ctx . '|splitread', 'false'),
            'switch' => array(
                'false' => array(
                    'desc' => 'Disabled',
                    'fields' => array()
                ),
                'true' => array(
                    'desc' => 'Enabled',
                    'fields' => array(
                        'read' => array(
                            'persistent' => $persistent,
                            'username' => $username,
                            'password' => $password,
                            'protocol' => $mysql_protocol,
                            'database' => $database,
                            'charset' => $charset
                        )
                    )
                )
            )
        );

        $custom_fields = array(
            'required' => true,
            'desc' => 'What database backend should we use?',
            'default' => $this->_default($ctx . '|phptype', 'false'),
            'switch' => array(
                'false' => array(
                    'desc' => '[None]',
                    'fields' => array()
                ),
                'ibase' => array(
                    'desc' => 'Firebird/InterBase',
                    'fields' => array(
                        'dbsyntax' => array(
                            '_type' => 'enum',
                            'desc' => 'The database syntax variant to use',
                            'required' => false,
                            'values' => array(
                                'ibase' => 'InterBase',
                                'firebird' => 'Firebird'
                            ),
                            'default' => $this->_default($ctx . '|dbsyntax', 'firebird')
                        ),
                        'persistent' => $persistent,
                        'hostspec' => $hostspec,
                        'username' => $username,
                        'password' => $password,
                        'database' => $database,
                        'buffers' => array(
                            '_type' => 'int',
                            'desc' => 'The number of database buffers to allocate',
                            'required' => false,
                            'default' => $this->_default($ctx . '|buffers', null)
                        ),
                        'dialect' => array(
                            '_type' => 'int',
                            'desc' => 'The default SQL dialect for any statement executed within a connection.',
                            'required' => false,
                            'default' => $this->_default($ctx . '|dialect', null)
                        ),
                        'role' => array(
                            '_type' => 'text',
                            'desc' => 'Role',
                            'required' => false,
                            'default' => $this->_default($ctx . '|role', null)),
                        'charset' => $charset
                    )
                ),
                'ifx' => array(
                    'desc' => 'Informix',
                    'fields' => array(
                        'persistent' => $persistent,
                        'username' => $username,
                        'password' => $password,
                        'database' => $database,
                        'charset' => $charset
                    )
                ),
                'mssql' => array(
                    'desc' => 'MS SQL/Sybase Server',
                    'fields' => array(
                        'persistent' => $persistent,
                        'hostspec' => $hostspec,
                        'username' => $username,
                        'password' => $password,
                        'port' => $port,
                        'database' => $database,
                        'charset' => $charset
                    )
                ),
                'mysql' => array(
                    'desc' => 'MySQL',
                    'fields' => array(
                        'persistent' => $persistent,
                        'username' => $username,
                        'password' => $password,
                        'protocol' => $mysql_protocol,
                        'database' => $database,
                        'charset' => $charset,
                        'ssl' => $ssl,
                        'ca' => $ca,
                        'splitread' => $splitread
                    )
                ),
                'mysqli' => array(
                    'desc' => 'MySQL (mysqli)',
                    'fields' => array(
                        'username' => $username,
                        'password' => $password,
                        'protocol' => $mysql_protocol,
                        'database' => $database,
                        'charset' => $charset,
                        'splitread' => $splitread,
                        'ssl' => $ssl,
                        'ca' => $ca
                )),
                'oci8' => array(
                    'desc' => 'Oracle',
                    'fields' => $oci8_fields
                ),
                'odbc' => array(
                    'desc' => 'ODBC',
                    'fields' => array(
                        'persistent' => $persistent,
                        'username' => $username,
                        'password' => $password,
                        'hostspec' => array(
                            '_type' => 'text',
                            'desc' => 'DSN',
                            'default' => $this->_default($ctx . '|hostspec', '')
                        ),
                        'dbsyntax' => array(
                            '_type' => 'enum',
                            'desc' => 'The database syntax variant to use',
                            'required' => false,
                            'values' => array(
                                'sql92' => 'SQL92',
                                'access' => 'Access',
                                'db2' => 'DB2',
                                'solid' => 'Solid',
                                'navision' => 'Navision',
                                'mssql' => 'MS SQL Server',
                                'sybase' => 'Sybase',
                                'mysql' => 'MySQL',
                                'mysqli' => 'MySQL (mysqli)',
                            ),
                            'default' => $this->_default($ctx . '|dbsyntax', 'sql92')
                        ),
                        'cursor' => array(
                            '_type' => 'enum',
                            'desc' => 'Cursor type',
                            'quote' => false,
                            'required' => false,
                            'values' => array(
                                'null' => 'None',
                                'SQL_CUR_DEFAULT' => 'Default',
                                'SQL_CUR_USE_DRIVER' => 'Use Driver',
                                'SQL_CUR_USE_ODBC' => 'Use ODBC',
                                'SQL_CUR_USE_IF_NEEDED' => 'Use If Needed'
                            ),
                            'default' => $this->_default($ctx . '|cursor', null)
                        ),
                        'charset' => $charset
                    )
                ),
                'pgsql' => array(
                    'desc' => 'PostgreSQL',
                    'fields' => array(
                        'persistent' => $persistent,
                        'username' => $username,
                        'password' => $password,
                        'protocol' => $protocol,
                        'database' => $database,
                        'charset' => $charset
                    )
                ),
                'sqlite' => array(
                    'desc' => 'SQLite',
                    'fields' => array(
                        'database' => array(
                            '_type' => 'text',
                            'required' => true,
                            'desc' => 'Absolute path to the database file',
                            'default' => $this->_default($ctx . '|database', '')
                        ),
                        'mode' => array(
                            '_type' => 'text',
                            'desc' => 'The mode to open the file with',
                            'default' => $this->_default($ctx . '|mode', '0644')
                        ),
                        'charset' => $charset
                    )
                )
            )
        );

        if (isset($node) && $node->getAttribute('baseconfig') == 'true') {
            return $custom_fields;
        }

        list($default, $isDefault) = $this->__default($ctx . '|' . (isset($node) ? $node->getAttribute('switchname') : $switchname), 'horde');
        $config = array(
            'desc' => 'Driver configuration',
            'default' => $default,
            'is_default' => $isDefault,
            'switch' => array(
                'horde' => array(
                    'desc' => 'Horde defaults',
                    'fields' => array()
                ),
                'custom' => array(
                    'desc' => 'Custom parameters',
                    'fields' => array(
                        'phptype' => $custom_fields
                    )
                )
            )
        );

        if (isset($node) && $node->hasChildNodes()) {
            $cur = array();
            $this->_parseLevel($cur, $node->childNodes, $ctx);
            $config['switch']['horde']['fields'] = array_merge($config['switch']['horde']['fields'], $cur);
            $config['switch']['custom']['fields'] = array_merge($config['switch']['custom']['fields'], $cur);
        }

        return $config;
    }

    /**
     * Returns the configuration tree for a VFS backend configuration to
     * replace a <configvfs> tag.
     * Subnodes will be parsed and added to both the Horde defaults and the
     * Custom configuration parts.
     *
     * @param string $ctx    The context of the <configvfs> tag.
     * @param DomNode $node  The DomNode representation of the <configvfs>
     *                       tag.
     *
     * @return array  An associative array with the VFS configuration tree.
     */
    protected function _configVFS($ctx, $node)
    {
        $sql = $this->_configSQL($ctx . '|params');
        $default = $node->getAttribute('default');
        $default = empty($default) ? 'horde' : $default;
        list($default, $isDefault) = $this->__default($ctx . '|' . $node->getAttribute('switchname'), $default);

        $config = array(
            'desc' => 'What VFS driver should we use?',
            'default' => $default,
            'is_default' => $isDefault,
            'switch' => array(
                'none' => array(
                    'desc' => 'None',
                    'fields' => array()
                ),
                'file' => array(
                    'desc' => 'Files on the local system',
                    'fields' => array(
                        'params' => array(
                            'vfsroot' => array(
                                '_type' => 'text',
                                'desc' => 'Where on the real filesystem should Horde use as root of the virtual filesystem?',
                                'default' => $this->_default($ctx . '|params|vfsroot', '/tmp')
                            )
                        )
                    )
                ),
                'sql' => array(
                    'desc' => 'SQL database',
                    'fields' => array(
                        'params' => array(
                            'driverconfig' => $sql
                        )
                    )
                ),
                'ssh2' => array(
                    'desc' => 'SSH2 (SFTP)',
                    'fields' => array(
                        'params' => array(
                            'hostspec' => array(
                                '_type' => 'text',
                                'required' => true,
                                'desc' => 'SSH server/host',
                                'default' => $this->_default($ctx . '|hostspec', '')
                            ),
                            'port' => array(
                                '_type' => 'text',
                                'required' => false,
                                'desc' => 'Port number on which SSH listens',
                                'default' => $this->_default($ctx . '|port', '22')
                            ),
                            'username' => array(
                                '_type' => 'text',
                                'required' => true,
                                'desc' => 'Username to connect to the SSH server',
                                'default' => $this->_default($ctx . '|username', '')
                            ),
                            'password' => array(
                                '_type' => 'text',
                                'required' => true,
                                'desc' => 'Password with which to connect',
                                'default' => $this->_default($ctx . '|password', '')
                            ),
                            'vfsroot' => array(
                                '_type' => 'text',
                                'desc' => 'Where on the real filesystem should Horde use as root of the virtual filesystem?',
                                'default' => $this->_default($ctx . '|vfsroot', '/tmp')
                            )
                        )
                    )
                )
            )
        );

        if (isset($node) && $node->getAttribute('baseconfig') != 'true') {
            $config['switch']['horde'] = array(
                'desc' => 'Horde defaults',
                'fields' => array()
            );
        }
        $cases = $this->_getSwitchValues($node, $ctx . '|params');
        foreach ($cases as $case => $fields) {
            if (isset($config['switch'][$case])) {
                $config['switch'][$case]['fields']['params'] = array_merge($config['switch'][$case]['fields']['params'], $fields['fields']);
            }
        }

        return $config;
    }

    /**
     * Returns a certain value from the current configuration array or
     * a default value, if not found.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return mixed  Either the value of the configuration array's requested
     *                key or the default value if the key wasn't found.
     */
    protected function _default($ctx, $default)
    {
        list ($ptr,) = $this->__default($ctx, $default);
        return $ptr;
    }

    /**
     * Returns whether a certain value from the current configuration array
     * exists or a default value will be used.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return boolean  Whether the default value will be used.
     */
    protected function _isDefault($ctx, $default)
    {
        list (,$isDefault) = $this->__default($ctx, $default);
        return $isDefault;
    }

    /**
     * Returns a certain value from the current configuration array or a
     * default value, if not found, and which of the values have been
     * returned.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return array  First element: either the value of the configuration
     *                array's requested key or the default value if the key
     *                wasn't found.
     *                Second element: whether the returned value was the
     *                default value.
     */
    protected function __default($ctx, $default)
    {
        $ctx = explode('|', $ctx);
        $ptr = $this->_currentConfig;

        for ($i = 0, $ctx_count = count($ctx); $i < $ctx_count; ++$i) {
            if (!isset($ptr[$ctx[$i]])) {
                return array($default, true);
            }

            $ptr = $ptr[$ctx[$i]];
        }

        return array($ptr, false);
    }

    /**
     * Returns a certain value from the current configuration file or
     * a default value, if not found.
     * It does NOT return the actual value, but the PHP expression as used
     * in the configuration file.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return mixed  Either the value of the configuration file's requested
     *                key or the default value if the key wasn't found.
     */
    protected function _defaultRaw($ctx, $default)
    {
        list ($ptr,) = $this->__defaultRaw($ctx, $default);
        return $ptr;
    }

    /**
     * Returns whether a certain value from the current configuration array
     * exists or a default value will be used.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return boolean  Whether the default value will be used.
     */
    protected function _isDefaultRaw($ctx, $default)
    {
        list (,$isDefault) = $this->__defaultRaw($ctx, $default);
        return $isDefault;
    }

    /**
     * Returns a certain value from the current configuration file or
     * a default value, if not found, and which of the values have been
     * returned.
     *
     * It does NOT return the actual value, but the PHP expression as used
     * in the configuration file.
     *
     * @param string $ctx     A string representing the key of the
     *                        configuration array to return.
     * @param mixed $default  The default value to return if the key wasn't
     *                        found.
     *
     * @return array  First element: either the value of the configuration
     *                array's requested key or the default value if the key
     *                wasn't found.
     *                Second element: whether the returned value was the
     *                default value.
     */
    protected function __defaultRaw($ctx, $default)
    {
        $ctx = explode('|', $ctx);
        $pattern = '/^\$conf\[\'' . implode("'\]\['", $ctx) . '\'\] = (.*);\r?$/m';

        return preg_match($pattern, $this->getPHPConfig(), $matches)
            ? array($matches[1], false)
            : array($default, true);
    }

    /**
     * Returns the content of all text node children of the specified node.
     *
     * @param DomNode $node  A DomNode object whose text node children to
     *                       return.
     *
     * @return string  The concatenated values of all text nodes.
     */
    protected function _getNodeOnlyText($node)
    {
        $text = '';

        if (!$node->hasChildNodes()) {
            return $node->textContent;
        }

        foreach ($node->childNodes as $tnode) {
            if ($tnode->nodeType == XML_TEXT_NODE) {
                $text .= $tnode->textContent;
            }
        }

        return trim($text);
    }

    /**
     * Returns an associative array containing all possible values of the
     * specified <configenum> tag.
     *
     * The keys contain the actual enum values while the values contain their
     * corresponding descriptions.
     *
     * @param DomNode $node  The DomNode representation of the <configenum>
     *                       tag whose values should be returned.
     *
     * @return array  An associative array with all possible enum values.
     */
    protected function _getEnumValues($node)
    {
        $values = array();

        if (!$node->hasChildNodes()) {
            return $values;
        }

        foreach ($node->childNodes as $vnode) {
            if ($vnode->nodeType == XML_ELEMENT_NODE &&
                $vnode->tagName == 'values') {
                if (!$vnode->hasChildNodes()) {
                    return array();
                }

                foreach ($vnode->childNodes as $value) {
                    if ($value->nodeType == XML_ELEMENT_NODE) {
                        if ($value->tagName == 'configspecial') {
                            return $this->_handleSpecials($value);
                        } elseif ($value->tagName == 'value') {
                            $text = $value->textContent;
                            $desc = $value->getAttribute('desc');
                            $values[$text] = empty($desc) ? $text : $desc;
                        }
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Returns a multidimensional associative array representing the specified
     * <configswitch> tag.
     *
     * @param DomNode &$node  The DomNode representation of the <configswitch>
     *                        tag to process.
     *
     * @return array  An associative array representing the node.
     */
    protected function _getSwitchValues(&$node, $curctx)
    {
        $values = array();

        if (!$node->hasChildNodes()) {
            return $values;
        }

        foreach ($node->childNodes as $case) {
            if ($case->nodeType == XML_ELEMENT_NODE) {
                $name = $case->getAttribute('name');
                $values[$name] = array(
                    'desc' => $case->getAttribute('desc'),
                    'fields' => array()
                );
                if ($case->hasChildNodes()) {
                    $this->_parseLevel($values[$name]['fields'], $case->childNodes, $curctx);
                }
            }
        }

        return $values;
    }

    /**
     * Returns an associative array containing the possible values of a
     * <configspecial> tag as used inside of enum configurations.
     *
     * @param DomNode $node  The DomNode representation of the <configspecial>
     *                       tag.
     *
     * @return array  An associative array with the possible values.
     */
    protected function _handleSpecials($node)
    {
        switch ($node->getAttribute('name')) {
        case 'list-horde-apps':
            $apps = Horde_Array::valuesToKeys($GLOBALS['registry']->listApps(array('hidden', 'notoolbar', 'active')));
            asort($apps);
            return $apps;

        case 'list-horde-languages':
            return array_map(create_function('$val', 'return preg_replace(array("/&#x([0-9a-f]{4});/ie", "/(&[^;]+;)/e"), array("Horde_String::convertCharset(pack(\"H*\", \"$1\"), \"ucs-2\", \"UTF-8\")", "Horde_String::convertCharset(html_entity_decode(\"$1\", ENT_COMPAT, \"iso-8859-1\"), \"iso-8859-1\", \"UTF-8\")"), $val);'), $GLOBALS['registry']->nlsconfig['languages']);

        case 'list-blocks':
            $collection = Horde_Block_Collection::singleton();
            return $collection->getBlocksList();

        case 'list-client-fields':
            global $registry;
            $f = array();
            if ($GLOBALS['registry']->hasMethod('clients/getClientSource')) {
                $addressbook = $GLOBALS['registry']->call('clients/getClientSource');
                try {
                    $fields = $GLOBALS['registry']->call('clients/clientFields', array($addressbook));
                } catch (Horde_Exception $e) {
                    try {
                        $fields = $GLOBALS['registry']->call('clients/fields', array($addressbook));
                    } catch (Horde_Exception $e) {
                        $fields = array();
                    }
                }

                foreach ($fields as $field) {
                    $f[$field['name']] = $field['label'];
                }
            }
            return $f;

        case 'list-contact-sources':
            try {
                return $GLOBALS['registry']->call('contacts/sources');
            } catch (Horde_Exception $e) {}
            break;
        }

        return array();
    }

    /**
     * Returns the specified string with escaped single quotes
     *
     * @param string $string  A string to escape.
     *
     * @return string  The specified string with single quotes being escaped.
     */
    protected function _quote($string)
    {
        return str_replace("'", "\'", $string);
    }

}
