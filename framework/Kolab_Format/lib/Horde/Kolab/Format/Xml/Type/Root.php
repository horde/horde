<?php
/**
 * Handles the document root.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Handles the document root.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Type_Root
{
    /**
     * The XML document this object works with.
     *
     * @var DOMDocument
     */
    private $_xmldoc;

    /**
     * The XPath query handler.
     *
     * @var DOMXpath
     */
    private $_xpath;

    /**
     * The parameters for this handler.
     *
     * @var array
     */
    private $_params;

    /**
     * Get the version of the root node.
     *
     * @var string
     */
    private $_version;

    /**
     * Constructor
     *
     * @param DOMDocument $xmldoc The XML document this object works with.
     * @param array       $params Additional parameters for this handler.
     */
    public function __construct($xmldoc, $params = array())
    {
        $this->_xmldoc = $xmldoc;
        $this->_xpath = new DOMXpath($this->_xmldoc);
        $this->_params = $params;
    }

    /**
     * Return the root node of the Kolab format object.
     *
     * @return DOMNode The root node.
     *
     * @throws Horde_Kolab_Format_Exception_InvalidRoot In case the root node
     * is missing.
     */
    public function load()
    {
        if (!($root = $this->findNode('/' . $this->_params['type']))) {
            throw new Horde_Kolab_Format_Exception_InvalidRoot(
                sprintf('Missing root node "%s"!', $this->_params['type'])
            );
        }
        $this->_version = $root->getAttribute('version');
        if (!$this->_isRelaxed()) {
            if (version_compare($this->_params['version'], $this->_version) < 0) {
                throw new Horde_Kolab_Format_Exception_InvalidRoot(
                    sprintf(
                        'Not attempting to read higher root version of %s with our version %s!',
                        $this->_version,
                        $this->_params['version']
                    )
                );
            }
        }
        return $root;
    }

    /**
     * Create the root node expected for the Kolab format if it is missing.
     *
     * @return DOMNode The (new) root node.
     *
     * @throws Horde_Kolab_Format_Exception_InvalidRoot In case the old root
     * node has a higher format version.
     */
    public function save()
    {
        if (!($root = $this->findNode('/' . $this->_params['type']))) {
            $root = $this->_xmldoc->createElement($this->_params['type']);
            $root->setAttribute('version', $this->_params['version']);
            $this->_version = $this->_params['version'];
            $this->_xmldoc->appendChild($root);
            return $root;
        }
        if (!$this->_isRelaxed()) {
            if (version_compare($this->_params['version'], $root->getAttribute('version')) < 0) {
                throw new Horde_Kolab_Format_Exception_InvalidRoot(
                    sprintf(
                        'Not attempting to overwrite higher root version of %s with our version %s!',
                        $root->getAttribute('version'),
                        $this->_params['version']
                    )
                );
            }
        }
        if ($this->_params['version'] != $root->getAttribute('version')) {
            $root->setAttribute('version', $this->_params['version']);
        }
        $this->_version = $this->_params['version'];
        return $root;
    }

    /**
     * Return the root node version.
     *
     * @return string The version number.
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Returns if the XML handling should be relaxed.
     *
     * @return boolean True if the XML should not be strict.
     */
    private function _isRelaxed()
    {
        return !empty($this->_params['relaxed']);
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
        return $this->_xpath->query($query);
    }

}
