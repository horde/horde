<?php
/**
 * The Horde_Help:: class provides an interface to the online help subsystem.
 *
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Help
{
    /* Raw help in the string. */
    const SOURCE_RAW = 0;

    /* Help text is in a file. */
    const SOURCE_FILE = 1;

    /**
     * A list of DOM help entry nodes.
     *
     * @var array
     */
    protected $_xml = array();

    /**
     * Constructor.
     *
     * @param integer $source  The source of the XML help data, based on the
     *                         SOURCE_* constants.
     * @param string $data     The data source. If $source is RAW, this is
     *                         XML text. If $source is FILE, this is the XML
     *                         filename.
     * @param array $views     Include these views.
     *
     * @throws Exception
     * @throws Horde_Exception
     */
    public function __construct($source, $data, array $views = array())
    {
        if (!Horde_Util::extensionExists('dom')) {
            throw new Horde_Exception('DOM not available.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');

        switch ($source) {
        case self::SOURCE_RAW:
            $dom->loadXML($data);
            break;

        case self::SOURCE_FILE:
            if (!@is_file($data)) {
                throw new Horde_Exception(Horde_Core_Translation::t("Help file not found."));
            }
            $dom->load($data);
            break;
        }

        /* Get list of active entries. */
        $this->_processXml($dom->getElementsByTagName('help')->item(0), $views);
    }

    /**
     */
    protected function _processXml(DOMElement $node, $views)
    {
        foreach ($node->childNodes as $val) {
            if ($val instanceof DOMElement) {
                switch ($val->tagName) {
                case 'entry':
                    $this->_xml[] = $val;
                    break;

                case 'view':
                    if (!empty($views) &&
                        $val->hasChildNodes() &&
                        in_array($val->getAttribute('id'), $views)) {
                        $this->_processXml($val, array());
                    }
                    break;
                }
            }
        }
    }

    /**
     * Looks up the requested entry in the XML help buffer.
     *
     * @param string $id  String containing the entry ID.
     *
     * @return string  The HTML formatted help entry.
     */
    public function lookup($id)
    {
        $out = '';

        foreach ($this->_xml as $entry) {
            if (($entry->getAttribute('id') == $id) &&
                $entry->hasChildNodes()) {
                foreach ($entry->childNodes as $child) {
                    if ($child instanceof DOMElement) {
                        switch ($child->tagName) {
                        case 'heading':
                            $out .= '<h2>' . $this->_processNode($child) . '</h2>';
                            break;

                        case 'para':
                            $out .= '<p>' . $this->_processNode($child) . '</p>';
                            break;

                        case 'raw':
                            $out .= '<p class="fixed">' . htmlspecialchars($this->_processNode($child)) . '</p>';
                            break;

                        case 'tip':
                            $out .= '<em class="helpTip">' . $this->_processNode($child) . '</em>';
                            break;

                        case 'title':
                            $out .= '<h1>' . $this->_processNode($child) . '</h1>';
                            break;

                        case 'warn':
                            $out .= '<em class="helpWarn">' . $this->_processNode($child) . '</em>';
                            break;
                        }
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Process a help node.
     *
     * @param DOMElement $node  A help node.
     *
     * @return string  HTML string.
     */
    protected function _processNode(DOMElement $node)
    {
        $out = '';

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                switch ($child->tagName) {
                case 'ref':
                    $out .= Horde::link(Horde::selfUrl()->add(array(
                        'module' => $child->getAttribute('module'),
                        'show' => 'entry',
                        'topic'  => $child->getAttribute('entry')
                    ))) . $child->textContent . '</a>';
                    break;

                case 'text':
                    $out .= $child->textContent;
                    break;

                case 'eref':
                    $out .= Horde::link($child->getAttribute('url'), null, '', '_blank') . $child->textContent . '</a>';
                    break;

                case 'href':
                    $out .= Horde::link(Horde::url($GLOBALS['registry']->get('webroot', $child->getAttribute('app') . '/' . $child->getAttribute('url'))), null, '', '_blank') . $child->textContent . '</a>';
                    break;

                case 'b':
                    $out .= '<strong>' . $this->_processNode($child) . '</strong>';
                    break;

                case 'i':
                    $out .= '<em>' . $this->_processNode($child) . '</em>';
                    break;

                case 'pre':
                    $out .= '<pre>' . $this->_processNode($child) . '</pre>';
                    break;

                case 'css':
                    $out .= '<span class="' . $child->getAttribute('class') . '">' . $this->_processNode($child) . '</span>';
                    break;
                }
            } else {
                $out .= $child->textContent;
            }
        }

        return $out;
    }

    /**
     * Returns a hash of all of the topics in this help buffer containing the
     * keyword specified.
     *
     * @param string $keyword  Search keyword.
     *
     * @return array  Hash of all of the search results.
     */
    public function search($keyword)
    {
        $results = array();

        foreach ($this->_xml as $elt) {
            if (stripos($elt->textContent, $keyword) !== false) {
                $results[$elt->getAttribute('id')] = $elt->getElementsByTagName('title')->item(0)->textContent;
            }
        }

        return $results;
    }

    /**
     * Returns a hash of all of the topics in this help buffer.
     *
     * @return array  Hash of all of the topics in this buffer (keys are IDs,
     *                values are the title names).
     */
    public function topics()
    {
        $topics = array();

        foreach ($this->_xml as $elt) {
            $topics[$elt->getAttribute('id')] = $elt->getElementsByTagName('title')->item(0)->textContent;
        }

        return $topics;
    }


    /**
     * Generates the HTML link that will pop up a help window for the
     * requested topic.
     *
     * @param string $module  The name of the current Horde module.
     * @param string $topic   The help topic to be displayed.
     *
     * @return string  The HTML to create the help link.
     */
    public static function link($module, $topic)
    {
        if (!$GLOBALS['registry']->showService('help')) {
            return '';
        }

        $url = $GLOBALS['registry']->getServiceLink('help', $module)->add('topic', $topic);
        return $url->link(array('title' => Horde_Core_Translation::t("Help"), 'class' => 'helplink', 'target' => 'hordehelpwin', 'onclick' => Horde::popupJs($url, array('urlencode' => true)) . 'return false;'))
            . Horde_Themes_Image::tag('help.png', array('alt' => Horde_Core_Translation::t("Help"))) . '</a>';
    }

}
