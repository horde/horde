<?php
/**
 * The Horde_Help:: class provides an interface to the online help subsystem.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
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
     * Handle for the XML object.
     *
     * @var SimpleXMLElement
     */
    protected $_xml;

    /**
     * Constructor.
     *
     * @param integer $source  The source of the XML help data, based on the
     *                         SOURCE_* constants.
     * @param array $data      The list of data sources to use.
     *
     * @throws Exception
     * @throws Horde_Exception
     */
    public function __construct($source, $data = array())
    {
        if (!Horde_Util::extensionExists('SimpleXML')) {
            throw new Horde_Exception('SimpleXML not available.');
        }

        $xml = array();

        switch ($source) {
        case self::SOURCE_RAW:
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($data[0]);
            $xml[] = $dom;
            break;

        case self::SOURCE_FILE:
            foreach (array_unique($data) as $val) {
                if (@is_file($val)) {
                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->load($val);
                    $xml[] = $dom;
                }
            }
            break;
        }

        if (empty($xml)) {
            throw new Horde_Exception('Help file not found.');
        }

        /* SimpleXML cannot deal with mixed text/data nodes. Convert all text
         * descendants of para to <text> tags */
        foreach ($xml as $dom) {
            $xpath = new DOMXpath($dom);
            foreach ($xpath->query('//para/text()') as $text) {
                $text->parentNode->replaceChild(new DOMElement('text', $text->nodeValue), $text);
            }
            $simplexml[] = simplexml_import_dom($dom);
        }

        $this->_xml = array_shift($simplexml);

        foreach ($simplexml as $val) {
            foreach ($val as $entry) {
                $this->_mergeXml($this->_xml, $entry);
            }
        }
    }

    /**
     * Merge XML elements together.
     *
     * @param SimpleXMLElement $base  Base element.
     * @param SimpleXMLElement $add   Element to add.
     */
    protected function _mergeXml($base, $add)
    {
        $new = $add->count()
            ? $base->addChild($add->getName())
            : $base->addChild($add->getName(), $add);

        foreach ($add->attributes() as $k => $v) {
            $new->addAttribute($k, $v);
        }

        foreach ($add->children() as $v) {
            $this->_mergeXml($new, $v);
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

        foreach ($this->_xml->entry as $entry) {
            if ($entry->attributes()->id == $id) {
                foreach ($entry->children() as $child) {
                    switch ($child->getName()) {
                    case 'heading':
                        $out .= '<h2>' . $this->_processNode($child) . '</h2>';
                        break;

                    case 'para':
                        $out .= '<p>' . $this->_processNode($child) . '</p>';
                        break;

                    case 'raw':
                        $out .= '<p class="fixed">' . htmlentities($this->_processNode($child)) . '</p>';
                        break;

                    case 'title':
                        $out .= '<h1>' . $this->_processNode($child) . '</h1>';
                        break;
                    }
                }
            }
        }

        return $out;
    }

    /**
     * Process a help node.
     *
     * @param SimpleXMLElement $node  An XML help node representation
     *
     * @return string  HTML string.
     */
    protected function _processNode($node)
    {
        if (!count($node->children())) {
            return strval($node);
        }

        $out = '';

        foreach ($node->children() as $child) {
            switch ($child->getName()) {
            case 'ref':
                $out .= Horde::link(Horde::selfUrl()->add(array(
                    'module' => $child->attributes()->module,
                    'show' => 'entry',
                    'topic'  => $child->attributes()->entry
                ))) . strval($child) . '</a>';
                break;

            case 'text':
                $out .= strval($child);
                break;

            case 'eref':
                $out .= Horde::link($child->attributes()->url, null, '', '_blank') . strval($child) . '</a>';
                break;

            case 'href':
                $out .= Horde::link(Horde::url($GLOBALS['registry']->get('webroot', $child->attributes()->app) . '/' . $child->attributes()->url), null, '', '_blank') . strval($child) . '</a>';
                break;

            case 'b':
                $out .= '<strong>' . strval($child) . '</strong>';
                break;

            case 'i':
                $out .= '<em>' . strval($child) . '</em>';
                break;

            case 'pre':
                $out .= '<pre>' . strval($child) . '</pre>';
                break;

            case 'tip':
                $out .= '<em class="helpTip">' . strval($child) . '</em>';
                break;

            case 'warn':
                $out .= '<em class="helpWarn">' . strval($child) . '</em>';
                break;

            case 'css':
                $out .= '<span class="' . $child->attributes()->class . '">' . strval($child) . '</span>';
                break;
            }
        }

        return $out;
    }

    /**
     * Returns a hash of all of the topics in this help buffer
     * containing the keyword specified.
     *
     * @return array  Hash of all of the search results.
     */
    public function search($keyword)
    {
        $results = array();

        foreach ($this->_xml->entry as $entry) {
            foreach ($entry as $elt) {
                if (stripos(strval($elt), $keyword) !== false) {
                    $results[strval($entry->attributes()->id)] = strval($entry->title);
                    break;
                }
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

        foreach ($this->_xml->entry as $elt) {
            $topics[strval($elt->attributes()->id)] = strval($elt->title);
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
    static public function link($module, $topic)
    {
        if (!$GLOBALS['registry']->showService('help')) {
            return '';
        }

        $url = $GLOBALS['registry']->getServiceLink('help', $module)->add('topic', $topic);
        return $url->link(array('title' => Horde_Core_Translation::t("Help"), 'class' => 'helplink', 'target' => 'hordehelpwin', 'onclick' => Horde::popupJs($url, array('urlencode' => true)) . 'return false;'))
            . Horde::img('help.png', Horde_Core_Translation::t("Help")) . '</a>';
    }

}
