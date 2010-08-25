<?php
/**
 * @category   Horde
 * @package    Support
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Utility class to help in loading DOM data from HTML strings.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @package    Support
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_Domhtml
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
     * Original charset of data.
     *
     * @var string
     */
    protected $_origCharset;

    /**
     * @param string $text
     * @param string $charset
     *
     * @throws Exception
     */
    public function __construct($text, $charset = null)
    {
        if (!extension_loaded('dom')) {
            throw new Exception('DOM extension is not available.');
        }

        $this->_origCharset = $charset;

        $old_error = libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($text);
        $this->encoding = $doc->encoding;

        if (!is_null($charset)) {
            if (!$doc->encoding) {
                $doc->loadHTML('<?xml encoding="UTF-8">' . Horde_String::convertCharset($text, $charset, 'UTF-8'));
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
     * @return string
     */
    public function returnHtml()
    {
        return Horde_String::convertCharset($this->dom->saveHTML(), $this->encoding, $this->_origCharset);
    }

}
