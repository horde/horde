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
     * Charset/encoding used in object.
     *
     * @var string
     */
    public $encoding;

    /**
     * Was charset forced by adding <?xml> tag?
     *
     * @var string
     */
    protected $_forced;

    /**
     * Original charset of data.
     *
     * @var string
     */
    protected $_origCharset;

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

        $this->_forced = null;
        $this->_origCharset = $charset;

        $old_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($text);
        $this->encoding = $doc->encoding;

        if (!is_null($charset)) {
            if (!$doc->encoding) {
                $this->_forced = '<?xml encoding="UTF-8">';
                $doc->loadHTML($this->_forced . Horde_String::convertCharset($text, $charset, 'UTF-8'));
                $this->encoding = 'UTF-8';
            } elseif ($doc->encoding != $charset) {
                /* If libxml can't auto-detect encoding, convert to what it
                 * *thinks* the encoding should be. */
                $doc->loadHTML(Horde_String::convertCharset($text, $charset, $doc->encoding));
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
        $ret = Horde_String::convertCharset($this->dom->saveHTML(), $this->encoding, $this->_origCharset);

        return $this->_forced
            ? str_replace($this->_forced, '', $ret)
            : $ret;
    }

}
