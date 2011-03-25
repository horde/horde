<?php
/**
 * @category   Horde
 * @package    Util
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */

/**
 * Utility class to help in loading DOM data from HTML strings.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @package    Util
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 */
class Horde_Domhtml
{
    /**
     * DOM object.
     *
     * @var DOMDocument
     */
    public $dom;

    /**
     * Original charset of data.
     *
     * @var string
     */
    protected $_origCharset;

    /**
     * Encoding tag added to beginning of output.
     *
     * @var string
     */
    protected $_xmlencoding = '';

    /**
     * Constructor.
     *
     * @param string $text     The text of the HTML document.
     * @param string $charset  The charset of the HTML document.
     *
     * @throws Exception
     */
    public function __construct($text, $charset = null)
    {
        if (!extension_loaded('dom')) {
            throw new Exception('DOM extension is not available.');
        }

        // Bug #9616: Make sure we have valid HTML input.
        if (!strlen($text)) {
            $text = '<html></html>';
        }

        $old_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument();

        if (is_null($charset)) {
            /* If no charset given, charset is whatever libxml tells us the
             * encoding should be defaulting to 'iso-8859-1'. */
            $doc->loadHTML($text);
            $this->_origCharset = $doc->encoding
                ? $doc->encoding
                : 'iso-8859-1';
        } else {
            /* Convert/try with UTF-8 first. */
            $this->_origCharset = Horde_String::lower($charset);
            $this->_xmlencoding = '<?xml encoding="UTF-8"?>';
            $doc->loadHTML($this->_xmlencoding . Horde_String::convertCharset($text, $charset, 'UTF-8'));

            if ($doc->encoding &&
                (Horde_String::lower($doc->encoding) != 'utf-8')) {
                /* Convert charset to what the HTML document says it SHOULD
                 * be. */
                $doc->loadHTML(Horde_String::convertCharset($text, $charset, $doc->encoding));
                $this->_xmlencoding = '';
            }
        }

        if ($old_error) {
            libxml_use_internal_errors(false);
        }

        $this->dom = $doc;
    }

    /**
     * Returns the HEAD element, or creates one if it doesn't exist.
     *
     * @return DOMElement  HEAD element.
     */
    public function getHead()
    {
        $head = $this->dom->getElementsByTagName('head');
        if ($head->length) {
            return $head->item(0);
        }

        $headelt = $this->dom->createElement('head');
        $this->dom->appendChild($headelt);

        return $headelt;
    }

    /**
     * Returns the full HTML text in the original charset.
     *
     * @return string  HTML text.
     */
    public function returnHtml()
    {
        $text = Horde_String::convertCharset($this->dom->saveHTML(), $this->dom->encoding || $this->_origCharset, $this->_origCharset);

        if (!$this->_xmlencoding ||
            (($pos = strpos($text, $this->_xmlencoding)) === false)) {
            return $text;
        }

        return substr_replace($text, '', $pos, strlen($this->_xmlencoding));
    }

    /**
     * Returns the body text in the original charset.
     *
     * @return string  HTML text.
     */
    public function returnBody()
    {
        $body = $this->dom->getElementsByTagName('body')->item(0);
        $text = '';

        if ($body && $body->hasChildNodes()) {
            foreach ($body->childNodes as $child) {
                $text .= $this->dom->saveXML($child);
            }
        }

        return Horde_String::convertCharset($text, 'UTF-8', $this->_origCharset);
    }

}
