<?php
/**
 * The Horde_Help:: class provides an interface to the online help subsystem.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jon Parise <jon@horde.org>
 * @category Horde
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
     * String containing the charset of the XML data source.
     *
     * @var string
     */
    protected $_charset = 'iso-8859-1';

    /**
     * Constructor.
     *
     * @param integer $source  The source of the XML help data, based on the
     *                         SOURCE_* constants.
     * @param array $data      The list of data sources to use.
     *
     * @throws Horde_Exception
     */
    public function __construct($source, $data = array())
    {
        if (!Horde_Util::extensionExists('SimpleXML')) {
            throw new Horde_Exception('SimpleXML not available.');
        }

        if (isset($GLOBALS['registry']->nlsconfig['charsets'][$GLOBALS['language']])) {
            $this->_charset = $GLOBALS['registry']->nlsconfig['charsets'][$GLOBALS['language']];
        }

        switch ($source) {
        case self::SOURCE_RAW:
            $this->_xml = new SimpleXMLElement($data[0]);
            break;

        case self::SOURCE_FILE:
            foreach ($data as $val) {
                if (@is_file($val)) {
                    $this->_xml = new SimpleXMLElement($val, null, true);
                    break;
                }
            }
            break;
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
                    case 'title':
                        $out .= '<h1>' . $this->_processNode($child) . '</h1>';
                        break;

                    case 'heading':
                        $out .= '<h2>' . $this->_processNode($child) . '</h2>';
                        break;

                    case 'para':
                        $out .= '<p>' . $this->_processNode($child) . '</h2>';
                        break;
                    }
                }

                break;
            }
        }

        return $out;
    }

    /**
     * TODO
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
        if (!Horde_Menu::showService('help')) {
            return '';
        }

        $url = Horde::getServiceLink('help', $module)->add('topic', $topic);
        return $url->link(array('title' => Horde_Core_Translation::t("Help"), 'class' => 'helplink', 'target' => 'hordehelpwin', 'onclick' => Horde::popupJs($url, array('urlencode' => true)) . 'return false;'))
            . Horde::img('help.png', Horde_Core_Translation::t("Help")) . '</a>';
    }


}
