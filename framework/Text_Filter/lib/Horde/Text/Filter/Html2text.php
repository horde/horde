<?php
/**
 * Takes HTML and converts it to formatted, plain text.
 *
 * Parameters:
 * <pre>
 * charset - (string) The charset to use for html_entity_decode() calls.
 * width - (integer) The wrapping width.
 * wrap - (boolean) Whether to wrap the text or not.
 * </pre>
 *
 * Copyright 2003-2004 Jon Abernathy <jon@chuggnutt.com>
 * Original source: http://www.chuggnutt.com/html2text.php
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Abernathy <jon@chuggnutt.com>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Text
 */
class Horde_Text_Filter_Html2text extends Horde_Text_Filter_Base
{
    /**
     * The list of links contained in the message.
     *
     * @var array
     */
    protected $_linkList = array();

    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'ISO-8859-1',
        'width' => 70,
        'wrap' => true
    );

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        $this->_linkList = array();
        return trim($text);
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $replace = array(
            // Non-legal carriage return.
            '/\r/' => ''
        );

        $regexp = array(
            // Newlines and tabs.
            '/[\n\t]+/' => ' ',

            // Normalize <br> (remove leading/trailing whitespace)
            '/\s*<br[^>]*>\s*/i' => '<br>',

            // <script>s -- which strip_tags() supposedly has problems with.
            '/<script(?:>|\s[^>]*>).*?<\/script\s*>/i' => '',

            // <style>s -- which strip_tags() supposedly has problems with.
            '/<style(?:>|\s[^>]*>).*?<\/style\s*>/i' => '',

            // h1 - h3
            '/<h[123](?:>|\s[^>]*>)(.+?)<\/h[123]\s*>/ie' => '"<br><br>" . strtoupper("\\1") . "<br><br>"',

            // h4 - h6
            '/<h[456](?:>|\s[^>]*>)(.+?)<\/h[456]\s*> ?/ie' => '"<br><br>" . ucwords("\\1") . "<br><br>"',

            // <p>
            '/\s*<p(?:>|\s[^>]*>)\s*/i' => '<br><br>',

            // <div>
            '/\s*<div(?:>|\s[^>]*>)\s*/i' => '<br>',

            // <b>
            '/<b(?:>|\s[^>]*>)(.+?)<\/b>/ie' => 'strtoupper("\\1")',

            // <strong>
            '/<strong(?:>|\s[^>]*>)(.+?)<\/strong>/ie' => 'strtoupper("\\1")',
            '/<span\s+style="font-weight:\s*bold.*">(.+?)<\/span>/ie' => 'strtoupper("\\1")',

            // <i>
            '/<i(?:>|\s[^>]*>)(.+?)<\/i>/i' => '/\\1/',

            // <em>
            '/<em(?:>|\s[^>]*>)(.+?)<\/em>/i' => '_\\1_',

            // <u>
            '/<u(?:>|\s[^>]*>)(.+?)<\/u>/i' => '_\\1_',

            // <ul>/<ol> and </ul>/</ol>
            '/\s*(<(u|o)l(?:>|\s[^>]*>)| ?<\/(u|o)l\s*>)\s*/i' => '<br><br>',

            // <li>
            '/\s*<li(?:>|\s[^>]*>)\s*/i' => '<br>  * ',

            // <hr>
            '/\s*<hr(?:>|\s[^>]*>)\s*/i' => '<br>-------------------------<br>',

            // <table> and </table>
            '/\s*(<table(?:>|\s[^>]*>)| ?<\/table\s*>)\s*/i' => '<br><br>',

            // <tr>
            '/\s*<tr(?:>|\s[^>]*>)\s*/i' => '<br>',

            // <td> and </td>
            '/\s*<td(?:>|\s[^>]*>)(.+?)<\/td>\s*/i' => '\\1<br>',

            // <th> and </th>
            '/\s*<th(?:>|\s[^>]*>)(.+?)<\/th>\s*/ie' => 'strtoupper("\\1") . "<br>"',

            // Some mailers (e.g. Hotmail) use the following div tag as a way
            // to define a block of text.
            '/<div class="?rte"?>(.+?)<\/div> ?/i' => '\\1<br>',

            // Cite blocks.
            '/\s*<blockquote\s+[^>]*(?:type="?cite"?|class="?gmail_quote"?)[^>]*>\s*/i' => '<hordecite>',

            // <br>
            '/<br[^>]*>/i' => "\n"
        );

        $regexp_callback = array(
            // <a href="">
            '/<a href="([^"]+)"[^>]*>(.+?)<\/a>/i' => array($this, 'buildLinkList')
        );

        return array(
            'regexp' => $regexp,
            'regexp_callback' => $regexp_callback,
            'replace' => $replace
        );
    }

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        /* Convert blockquote tags. */
        return $text;
     //   if (strpos($text, chr(0)) !== false) {
        //    $text = $this->_blockQuote($text);
//        }

        /* Strip any other HTML tags. */
        $text = strip_tags($text);

        /* Convert HTML entities. */
        $text = html_entity_decode($text, ENT_QUOTES, $this->_params['charset']);

        /* Bring down number of empty lines to 2 max. */
        $text = preg_replace(array("/\n[[:space:]]+\n/", "/[\n]{3,}/"), "\n\n", $text);

        /* Wrap the text to a readable format. */
        if ($this->_params['wrap']) {
            $text = wordwrap($text, $this->_params['width']);
        }

        /* Add link list. */
        if (!empty($this->_linkList)) {
            $text .= "\n\n" . _("Links") . ":\n" .
                str_repeat('-', Horde_String::length(_("Links")) + 1) . "\n";
            foreach ($this->_linkList as $key => $val) {
                $text .= '[' . ($key + 1) . '] ' . $val . "\n";
            }
        }

        return trim(rtrim($text), "\n");
    }

    /**
     * Replaces blockquote tags with > quotes.
     *
     * @param string $text  The text to quote.
     *
     * @return string  The quoted text.
     */
    protected function _blockQuote($text)
    {
        return preg_replace(
            '/([^\x00\x01]*)\x00(((?>[^\x00\x01]*)|(?R))*)\x01([^\x00\x01]*)/se',
            "stripslashes('$1') . \"\n\n\" . \$this->_quote('$2') . \"\n\n\" . stripslashes('$4')",
            $text);
    }

    /**
     * Quotes a chunk of text.
     *
     * @param string $text  The text to quote.
     *
     * @return string  The quoted text.
     */
    protected function _quote($text)
    {
        $text = stripslashes($text);
        if (strpos($text, chr(0)) !== false) {
            $text = stripslashes($this->_blockQuote($text));
        }

        $text = rtrim(strip_tags($text));
        if ($this->_params['wrap']) {
            $text = wordwrap($text, $this->_params['width'] - 2);
        }

        return preg_replace(array('/^/m', '/(\n>\s*$){3,}/m', '/^>\s+$/m'),
                            array('> ', "\n> ", '>'),
                            $text);
    }

    /**
     * Helper function called by preg_replace() on link replacement.
     *
     * Maintains an internal list of links to be displayed at the end
     * of the text, with numeric indices to the original point in the
     * text they appeared.
     *
     * @param array $matches  Match information:
     * <pre>
     * [1] URL of the link.
     * [2] Part of the text to associate number with.
     * </pre>
     *
     * @return string  The link replacement.
     */
    public function buildLinkList($matches)
    {
        $link = $matches[1];
        $display = $matches[2];

        if ($link == strip_tags($display)) {
            return $display;
        }

        $parsed_link = parse_url($link);
        $parsed_display = parse_url(strip_tags(preg_replace('/^&lt;|&gt;$/', '', $display)));

        if (isset($parsed_link['path'])) {
            $parsed_link['path'] = trim($parsed_link['path'], '/');
            if (!strlen($parsed_link['path'])) {
                unset($parsed_link['path']);
            }
        }

        if (isset($parsed_display['path'])) {
            $parsed_display['path'] = trim($parsed_display['path'], '/');
            if (!strlen($parsed_display['path'])) {
                unset($parsed_display['path']);
            }
        }

        if (((!isset($parsed_link['host']) &&
              !isset($parsed_display['host'])) ||
             (isset($parsed_link['host']) &&
              isset($parsed_display['host']) &&
              $parsed_link['host'] == $parsed_display['host'])) &&
            ((!isset($parsed_link['path']) &&
              !isset($parsed_display['path'])) ||
             (isset($parsed_link['path']) &&
              isset($parsed_display['path']) &&
              $parsed_link['path'] == $parsed_display['path']))) {
            return $display;
        }

        $this->_linkList[] = $link;

        return $display . '[' . count($this->_linkList) . ']';
    }

}
