<?php
/**
 * Handles package.xml files.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Handles package.xml files.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml
{
    /** The package.xml namespace */
    const XMLNAMESPACE = 'http://pear.php.net/dtd/package-2.0';

    /**
     * The parsed XML.
     *
     * @var DOMDocument
     */
    private $_xml;

    /**
     * The path to the XML file.
     *
     * @var string
     */
    private $_path;

    /**
     * The XPath query handler.
     *
     * @var DOMXpath
     */
    private $_xpath;

    /**
     * The XPath namespace prefix (if necessary).
     *
     * @var string
     */
    private $_namespace_prefix = '';

    /**
     * The factory for required instances.
     *
     * @var Horde_Pear_Package_Xml_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param resource|string $xml The package.xml as stream or path.
     */
    public function __construct($xml, $factory = null)
    {
        if (is_resource($xml)) {
            rewind($xml);
        } else {
            $this->_path = $xml;
            $xml = fopen($xml, 'r');
        }
        $this->_xml = new DOMDocument('1.0', 'UTF-8');
        $this->_xml->loadXML(stream_get_contents($xml));
        $rootNamespace = $this->_xml->lookupNamespaceUri($this->_xml->namespaceURI);
        $this->_xpath = new DOMXpath($this->_xml);
        if ($rootNamespace !== null) {
            $this->_xpath->registerNamespace('p', $rootNamespace);
            $this->_namespace_prefix = 'p:';
        }
        if ($factory === null) {
            $this->_factory = new Horde_Pear_Package_Xml_Factory();
        } else {
            $this->_factory = $factory;
        }
    }

    /**
     * Return the list of contents.
     *
     * @return Horde_Pear_Package_Contents_List The contents.
     */
    public function getContent($type = 'horde', $path = null)
    {
        if ($path === null) {
            if ($this->_path === null) {
                throw new Horde_Pear_Exception('The path has not been provided!');
            }
            $path = $this->_path;
        }
        if (!is_object($type)) {
            if ($type == 'horde' || !class_exists($type)) {
                $type = 'Horde_Pear_Package_Type_' . ucfirst($type);
            }
            $type = new $type(dirname($this->_path));
        }
        return new Horde_Pear_Package_Contents_List($type);
    }

    /**
     * Return the package name.
     *
     * @return string The name of the package.
     */
    public function getName()
    {
        return $this->getNodeText('/p:package/p:name');
    }

    /**
     * Return the package channel.
     *
     * @return string The channel of the package.
     */
    public function getChannel()
    {
        return $this->getNodeText('/p:package/p:channel');
    }

    /**
     * Return the package summary.
     *
     * @return string The summary of the package.
     */
    public function getSummary()
    {
        return $this->getNodeText('/p:package/p:summary');
    }

    /**
     * Return the package description.
     *
     * @return string The description of the package.
     */
    public function getDescription()
    {
        return $this->getNodeText('/p:package/p:description');
    }

    /**
     * Return the package version.
     *
     * @return string The version of the package.
     */
    public function getVersion()
    {
        return $this->getNodeText('/p:package/p:version/p:release');
    }

    /**
     * Return the release date.
     *
     * @return string The date for the current release.
     */
    public function getDate()
    {
        return $this->getNodeText('/p:package/p:date');
    }

    /**
     * Return the package notes.
     *
     * @return string The notes for the current release.
     */
    public function getNotes()
    {
        return $this->getNodeText('/p:package/p:notes');
    }

    /**
     * Return the stability of the release or api.
     *
     * @param string $key "release" or "api"
     *
     * @return string The stability.
     */
    public function getState($key = 'release')
    {
        if (in_array($key, array('release', 'api'))) {
            return $this->getNodeText('/p:package/p:stability/p:' . $key);
        }
        throw new Horde_Pear_Exception(sprintf('Unsupported state "%s"!', $key));
    }

    /**
     * Return the package dependencies.
     *
     * @return array The package dependencies.
     */
    public function getDependencies()
    {
        $result = array();
        $this->_completeDependencies(
            $this->findNode('/p:package/p:dependencies/p:required'),
            $result,
            'no'
        );
        $this->_completeDependencies(
            $this->findNode('/p:package/p:dependencies/p:optional'),
            $result,
            'yes'
        );
        return $result;
    }

    /**
     * Complete the dependency information.
     *
     * @param DOMNode $parent   The parent node ("required" or "optional").
     * @param array   &$result  The result array.
     * @param string  $optional Optional dependency or not?
     *
     * @return NULL
     */
    private function _completeDependencies($parent, &$result, $optional)
    {
        if ($parent === false) {
            return;
        }
        foreach ($parent->childNodes as $dep) {
            if ($dep->nodeType == XML_TEXT_NODE) {
                continue;
            }
            $input = array();
            $this->_dependencyInputValue($input, 'min', $dep);
            $this->_dependencyInputValue($input, 'max', $dep);
            $this->_dependencyInputValue($input, 'name', $dep);
            $this->_dependencyInputValue($input, 'channel', $dep);
            $this->_dependencyInputValue($input, 'conflicts', $dep);
            Horde_Pear_Package_Dependencies::addDependency(
                $input, $dep->nodeName, $optional, $result
            );
        }
    }

    /**
     * Generate one element of the input data.
     *
     * @param array   &$input The input array.
     * @param string  $name   Value name.
     * @param DOMNode $node   The dependency node.
     *
     * @return NULL
     */
    private function _dependencyInputValue(&$input, $name, $node)
    {
        if (($result = $this->getNodeTextRelativeTo('./p:' . $name, $node)) !== false) {
            $input[$name] = $result;
        }
    }

    /**
     * Return the license name.
     *
     * @return string The name of the license.
     */
    public function getLicense()
    {
        return $this->getNodeText('/p:package/p:license');
    }

    /**
     * Return the URL to the license information.
     *
     * @return string The license URI.
     */
    public function getLicenseLocation()
    {
        $node = $this->findNode('/p:package/p:license');
        if (empty($node)) {
            return '';
        }
        return $node->getAttribute('uri');
    }

    /**
     * Return the package lead developers.
     *
     * @return string The package lead developers.
     */
    public function getLeads()
    {
        $result = array();
        foreach ($this->findNodes('/p:package/p:lead') as $lead) {
            $result[] = array(
                'name' => $this->getNodeTextRelativeTo('./p:name', $lead),
                'user' => $this->getNodeTextRelativeTo('./p:user', $lead),
                'email' => $this->getNodeTextRelativeTo('./p:email', $lead),
                'active' => $this->getNodeTextRelativeTo('./p:active', $lead),
            );
        }
        return $result;
    }

    /**
     * Catch undefined method calls and try to run them as task.
     *
     * @param string $name      The method/task name.
     * @param array  $arguments The arguments for the call.
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 6) == 'create') {
            return $this->_factory->create(substr($name, 6), $arguments);
        } else {
            array_unshift($arguments, $this);
            $this->_factory->createTask($name, $arguments)->run();
        }
    }

    /**
     * Mark the package as being release and set the timestamps to now.
     *
     * @return NULL
     */
    public function timestamp()
    {
        $this->replaceTextNode('/p:package/p:date', date('Y-m-d'));
        $this->replaceTextNode('/p:package/p:time', date('H:i:s'));

        $release = $this->_requireCurrentRelease();

        $this->replaceTextNodeRelativeTo('./p:date', $release, date('Y-m-d'));
    }

    /**
     * Synchronizes the current version information with the release information
     * in the changelog.
     *
     * @return NULL
     */
    public function syncCurrentVersion()
    {
        $date = $this->getNodeText('/p:package/p:date');
        $notes = $this->getNodeText('/p:package/p:notes');
        $api = $this->getNodeText('/p:package/p:version/p:api');
        $stability_api = $this->getNodeText('/p:package/p:stability/p:api');
        $stability_release = $this->getNodeText(
            '/p:package/p:stability/p:release'
        );

        $release = $this->_requireCurrentRelease();

        $this->replaceTextNodeRelativeTo('./p:date', $release, $date);
        $this->replaceTextNodeRelativeTo(
            './p:notes', $release, $notes . '  '
        );
        $this->replaceTextNodeRelativeTo(
            './p:license',
            $release,
            $this->getLicense(),
            array('uri' => $this->getLicenseLocation())
        );
        $version_node = $this->findNodeRelativeTo(
            './p:version', $release
        );
        $this->replaceTextNodeRelativeTo(
            './p:api', $version_node, $api
        );
        $stability_node = $this->findNodeRelativeTo(
            './p:stability', $release
        );
        $this->replaceTextNodeRelativeTo(
            './p:api', $stability_node, $stability_api
        );
        $this->replaceTextNodeRelativeTo(
            './p:release', $stability_node, $stability_release
        );
    }

    /**
     * Add a new note to the package.xml
     *
     * @param string $note The note text.
     *
     * @return NULL
     */
    public function addNote($note)
    {
        $notes = trim($this->getNodeText('/p:package/p:notes'));
        if ($notes != '*') {
            $new_notes = "\n* " . $note . "\n" . $notes . "\n ";
        } else {
            $new_notes = "\n* " . $note . "\n ";
        }
        $this->replaceTextNode('/p:package/p:notes', $new_notes);

        $release = $this->_fetchCurrentRelease();
        if ($release !== null) {
            $this->replaceTextNodeRelativeTo(
                './p:notes', $release, $new_notes . '  '
            );
        }
    }

    /**
     * Set the version in the package.xml
     *
     * @param string $rel_version The new release version number.
     * @param string $api_version The new api version number.
     *
     * @return NULL
     */
    public function setVersion($rel_version = null, $api_version = null)
    {
        $release = $this->findNodeRelativeTo(
            './p:version',
            $this->_requireCurrentRelease()
        );
        $version = $this->findNode('/p:package/p:version');
        if ($rel_version) {
            $this->replaceTextNodeRelativeTo(
                './p:release', $version, $rel_version
            );
            $this->replaceTextNodeRelativeTo(
                './p:release', $release, $rel_version
            );
        }
        if ($api_version) {
            $this->replaceTextNodeRelativeTo(
                './p:api', $version, $api_version
            );
            $this->replaceTextNodeRelativeTo(
                './p:api', $release, $api_version
            );
        }
    }

    /**
     * Set the state in the package.xml
     *
     * @param string $rel_state The new release state number.
     * @param string $api_state The new api state number.
     *
     * @return NULL
     */
    public function setState($rel_state = null, $api_state = null)
    {
        $release = $this->findNodeRelativeTo(
            './p:stability',
            $this->_requireCurrentRelease()
        );
        $stability = $this->findNode('/p:package/p:stability');
        if ($rel_state) {
            $this->replaceTextNodeRelativeTo(
                './p:release', $stability, $rel_state
            );
            $this->replaceTextNodeRelativeTo(
                './p:release', $release, $rel_state
            );
        }
        if ($api_state) {
            $this->replaceTextNodeRelativeTo(
                './p:api', $stability, $api_state
            );
            $this->replaceTextNodeRelativeTo(
                './p:api', $release, $api_state
            );
        }
    }

    /**
     * Add the next version to the package.xml
     *
     * @param string $version           The new version number.
     * @param string $initial_note      The text for the initial note.
     * @param string $stability_api     The API stability for the next release.
     * @param string $stability_release The stability for the next release.
     *
     * @return NULL
     */
    public function addNextVersion($version, $initial_note,
                                   $stability_api = null,
                                   $stability_release = null)
    {
        $notes = "\n* " . $initial_note . "\n ";
        $api = $this->getNodeText('/p:package/p:version/p:api');
        if ($stability_api === null) {
            $stability_api = $this->getNodeText('/p:package/p:stability/p:api');
        }
        if ($stability_release === null) {
            $stability_release = $this->getNodeText(
                '/p:package/p:stability/p:release'
            );
        }
        $version_node = $this->findNode('/p:package/p:version');
        $this->replaceTextNodeRelativeTo(
            './p:release', $version_node, $version
        );
        $this->replaceTextNode('/p:package/p:notes', $notes);
        $this->replaceTextNode('/p:package/p:date', date('Y-m-d'));
        $this->replaceTextNode('/p:package/p:time', date('H:i:s'));

        $changelog = $this->findNode('/p:package/p:changelog');
        $this->_insertWhiteSpace($changelog, ' ');

        $release = $this->_xml->createElementNS(self::XMLNAMESPACE, 'release');
        $this->_appendVersion($release, $version, $api, "\n   ");
        $this->_appendStability($release, $stability_release, $stability_api, "\n   ");
        $this->_appendChild($release, 'date', date('Y-m-d'), "\n   ");
        $this->_appendLicense(
            $release,
            $this->getLicense(),
            $this->getLicenseLocation(),
            "\n   "
        );
        $this->_appendChild($release, 'notes', $notes . '  ', "\n   ");
        $this->_insertWhiteSpace($release, "\n  ");
        $changelog->appendChild($release);
        $this->_insertWhiteSpace($changelog, "\n ");
    }

    /**
     * Append version information.
     *
     * @param DOMNode $parent  The parent DOMNode.
     * @param string  $version The version.
     * @param string  $api     The api version.
     * @param string  $ws      Additional white space that should be inserted.
     *
     * @return NULL
     */
    private function _appendVersion($parent, $version, $api, $ws = '')
    {
        $this->_insertWhiteSpace($parent, $ws);
        $node = $this->_xml->createElementNS(self::XMLNAMESPACE, 'version');
        $this->_appendChild($node, 'release', $version, "\n    ");
        $this->_appendChild($node, 'api', $api, "\n    ");
        $parent->appendChild($node);
    }

    /**
     * Append stability information.
     *
     * @param DOMNode $parent  The parent DOMNode.
     * @param string  $release The release stability.
     * @param string  $api     The api stability.
     * @param string  $ws      Additional white space that should be inserted.
     *
     * @return NULL
     */
    private function _appendStability($parent, $release, $api, $ws = null)
    {
        $this->_insertWhiteSpace($parent, $ws);
        $node = $this->_xml->createElementNS(self::XMLNAMESPACE, 'stability');
        $this->_appendChild($node, 'release', $release, "\n    ");
        $this->_appendChild($node, 'api', $api, "\n    ");
        $parent->appendChild($node);
    }

    /**
     * Append license information.
     *
     * @param DOMNode $parent  The parent DOMNode.
     * @param string  $license The license name.
     * @param string  $uri     The license URI.
     * @param string  $ws      Additional white space that should be inserted.
     *
     * @return NULL
     */
    private function _appendLicense($parent, $license, $uri, $ws = null)
    {
        $this->_insertWhiteSpace($parent, $ws);
        $new_node = $this->_xml->createElementNS(
            self::XMLNAMESPACE, 'license'
        );
        $text = $this->_xml->createTextNode($license);
        $new_node->appendChild($text);
        $new_node->setAttribute('uri', $uri);
        $parent->appendChild($new_node);
    }

    /**
     * Fetch the node holding the current release information in the changelog
     * and fail if there is no such node.
     *
     * @return DOMElement|NULL The release node.
     *
     * @throws Horde_Pear_Exception If the node does not exist.
     */
    private function _requireCurrentRelease()
    {
        $release = $this->_fetchCurrentRelease();
        if ($release === null) {
            throw new Horde_Pear_Exception('No release in the changelog matches the current version!');
        }
        return $release;
    }

    /**
     * Fetch the node holding the current release information in the changelog.
     *
     * @return DOMElement|NULL The release node or empty if no such node was found.
     */
    private function _fetchCurrentRelease()
    {
        $version = $this->getNodeText('/p:package/p:version/p:release');
        foreach ($this->findNodes('/p:package/p:changelog/p:release') as $release) {
            if ($this->getNodeTextRelativeTo('./p:version/p:release', $release) == $version) {
                return $release;
            }
        }
    }

    /**
     * Return the complete package.xml as string.
     *
     * @return string The package.xml content.
     */
    public function __toString()
    {
        $result = $this->_xml->saveXML();
        $result = preg_replace(
            '#<package (.*) (packagerversion="[.0-9]*" version="2.0")#',
            '<package \2 \1',
            $result
        );
        $result = preg_replace("#'#", '&apos;', $result);
        return preg_replace('#"/>#', '" />', $result);
    }

    /**
     * Return a single named node matching the given XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNode|false The named DOMNode or empty if no node was found.
     */
    public function findNode($query)
    {
        return $this->_findSingleNode($this->findNodes($query));
    }

    /**
     * Return a single named node below the given context matching the given
     * XPath query.
     *
     * @param string  $query   The query.
     * @param DOMNode $context Search below this node.
     *
     * @return DOMNode|false The named DOMNode or empty if no node was found.
     */
    public function findNodeRelativeTo($query, DOMNode $context)
    {
        return $this->_findSingleNode(
            $this->findNodesRelativeTo($query, $context)
        );
    }

    /**
     * Return a single node for the result set.
     *
     * @param DOMNodeList $result The query result.
     *
     * @return DOMNode|false The DOMNode or empty if no node was found.
     */
    private function _findSingleNode($result)
    {
        if ($result->length) {
            return $result->item(0);
        }
        return false;
    }

    /**
     * Return all nodes matching the given XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function findNodes($query)
    {
        $query = preg_replace('#/p:#', '/' . $this->_namespace_prefix, $query);
        return $this->_xpath->query($query);
    }

    /**
     * Return all nodes matching the given XPath query.
     *
     * @param string $query The query.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function findNodesRelativeTo($query, $context)
    {
        $query = preg_replace('#/p:#', '/' . $this->_namespace_prefix, $query);
        return $this->_xpath->query($query, $context);
    }

    /**
     * Return the content of a single named node matching the given XPath query.
     *
     * @param string $path The node path.
     *
     * @return string|false The node content as string or empty if no node was
     *                      found.
     */
    public function getNodeText($path)
    {
        if ($node = $this->findNode($path)) {
            return $node->textContent;
        }
        return false;
    }

    /**
     * Return the content of a single named node below the given context
     * and matching the given XPath query.
     *
     * @param string  $path    The node path.
     * @param DOMNode $context Search below this node.
     *
     * @return string|false The node content as string or empty if no node was
     *                      found.
     */
    public function getNodeTextRelativeTo($path, DOMNode $context)
    {
        if ($node = $this->findNodeRelativeTo($path, $context)) {
            return $node->textContent;
        }
        return false;
    }

    /**
     * Replace a specific text node
     *
     * @param string $path  The XPath query pointing to the node.
     * @param string $value The new text value.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function replaceTextNode($path, $value)
    {
        if ($node = $this->findNode($path)) {
            $this->_xml->documentElement->replaceChild(
                $this->_replacementNode($node, $value), $node
            );
        }
    }

    /**
     * Replace a specific text node
     *
     * @param string  $path      The XPath query pointing to the node.
     * @param DOMNode $context   Search below this node.
     * @param string  $value     The new text value.
     * @param array   $attribues Attributes to add to the node.
     *
     * @return DOMNodeList The list of DOMNodes.
     */
    public function replaceTextNodeRelativeTo($path, DOMNode $context, $value,
                                              $attributes = array())
    {
        if ($node = $this->findNodeRelativeTo($path, $context)) {
            $new_node = $this->_replacementNode($node, $value);
            foreach ($attributes as $name => $value) {
                $new_node->setAttribute($name, $value);
            }
            $context->replaceChild($new_node, $node);
        }
    }

    /**
     * Generate a replacement node.
     *
     * @param DOMNode $old_node The old DOMNode to be replaced.
     * @param string  $value    The new text value.
     *
     * @return DOMNode The new DOMNode.
     */
    private function _replacementNode($old_node, $value)
    {
        $new_node = $this->_xml->createElementNS(
            self::XMLNAMESPACE, $old_node->tagName
        );
        $text = $this->_xml->createTextNode($value);
        $new_node->appendChild($text);
        return $new_node;
    }

    /**
     * Append a new child.
     *
     * @param DOMNode $parent The parent DOMNode.
     * @param string  $name   The tag name of the new node.
     * @param string  $value  The text content of the new node.
     * @param string  $ws     Additional white space that should be inserted.
     *
     * @return NULL
     */
    private function _appendChild($parent, $name, $value, $ws = '')
    {
        $this->_insertWhiteSpace($parent, $ws);
        $new_node = $this->_xml->createElementNS(
            self::XMLNAMESPACE, $name
        );
        $text = $this->_xml->createTextNode($value);
        $new_node->appendChild($text);
        $parent->appendChild($new_node);
    }

    /**
     * Insert some white space.
     *
     * @param DOMNode $parent The parent DOMNode.
     * @param string  $ws     Additional white space that should be inserted.
     *
     * @return DOMNode The inserted white space node.
     */
    public function _insertWhiteSpace($parent, $ws)
    {
        $ws_node = $this->_xml->createTextNode($ws);
        $parent->appendChild($ws_node);
        return $ws_node;
    }


    public function insert($elements, $point)
    {
        if (!is_array($elements)) {
            $elements = array($elements);
        }
        $node = null;
        foreach ($elements as $key => $element) {
            if (is_string($element)) {
                $element = $this->createText($element);
            } else if (is_array($element)) {
                $node = $element = $this->createNode($key, $element);
            }
            $point->parentNode->insertBefore($element, $point);
        }
        return $node;
    }

    public function append($elements, $parent)
    {
        if (!is_array($elements)) {
            $elements = array($elements);
        }
        $node = null;
        foreach ($elements as $key => $element) {
            if (is_string($element)) {
                $element = $this->createText($element);
            } else if (is_array($element)) {
                $node = $element = $this->createNode($key, $element);
            }
            $parent->appendChild($element);
        }
        return $node;
    }

    public function createText($text)
    {
        return $this->_xml->createTextNode($text);
    }

    public function createComment($comment)
    {
        return $this->_xml->createComment($comment);
    }

    public function createNode($name, $attributes = array())
    {
        $node = $this->_xml->createElementNS(self::XMLNAMESPACE, $name);
        foreach ($attributes as $key => $value) {
            $node->setAttribute($key, $value);
        }
        return $node;
    }

    public function removeWhitespace($node)
    {
        if ($node) {
            $ws = trim($node->textContent);
            if (empty($ws)) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    public function removeComment($node, $comment)
    {
        if ($node) {
            $current = trim($node->textContent);
            if ($current == $comment) {
                $node->parentNode->removeChild($node);
            }
        }
    }
}