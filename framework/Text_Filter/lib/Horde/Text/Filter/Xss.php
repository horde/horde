<?php
/**
 * This filter attempts to make HTML safe for viewing. IT IS NOT PERFECT. If
 * you enable HTML viewing, you are opening a security hole.
 *
 * Filter parameters:
 * ------------------
 * <pre>
 * 'charset' - (string) The charset of the text.
 *             DEFAULT: UTF-8
 * 'noprefetch' - (boolean) Disable DNS pre-fetching? See:
 *                https://developer.mozilla.org/En/Controlling_DNS_prefetching
 *                DEFAULT: false
 * 'return_document' - (string) If true, returns a full HTML representation of
 *                     the document.
 *                     DEFAULT: false (returns the contents contained inside
 *                              the BODY tag)
 * 'return_dom' - (boolean) If true, return a Horde_Support_Domhtml object
 *                instead of HTML text (overrides return_document).
 *                DEFAULT: false
 * 'strip_styles' - (boolean) Strip style tags?
 *                  DEFAULT: true
 * 'strip_style_attributes' - (boolean) Strip style attributes in all tags?
 *                            DEFAULT: true
 * </pre>
 *
 * @todo http://blog.astrumfutura.com/archives/430-html-Sanitisation-Benchmarking-With-Wibble-ZF-Proposal.html
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Xss extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'UTF-8',
        'noprefetch' => false,
        'return_document' => false,
        'return_dom' => false,
        'strip_styles' => true,
        'strip_style_attributes' => true
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        return array('regexp' => array(
            /* Remove all control characters. */
            '/[\x00-\x08\x0e-\x1f]/' => '',

            /* Change space entities to space characters. */
            '/&#(?:x0*20|0*32);?/i' => ' ',

            /* If we have a semicolon, it is deterministically detectable and
             * fixable, without introducing collateral damage. */
            '/&#x?0*(?:[9A-D]|1[0-3]);/i' => '&nbsp;',

            /* Hex numbers (usually having an x prefix) are also deterministic,
             * even if we don't have the semi. Note that some browsers will
             * treat &#a or &#0a as a hex number even without the x prefix;
             * hence /x?/ which will cover those cases in this rule. */
            '/&#x?0*[9A-D]([^0-9A-F]|$)/i' => '&nbsp\\1',

            /* Decimal numbers without trailing semicolons. The problem is
             * that some browsers will interpret &#10a as "\na", some as
             * "&#x10a" so we have to clean the &#10 to be safe for the "\na"
             * case at the expense of mangling a valid entity in other cases.
             * (Solution for valid HTML authors: always use the semicolon.) */
            '/&#0*(?:9|1[0-3])([^0-9]|$)/i' => '&nbsp\\1',

            /* Remove overly long numeric entities. */
            '/&#x?0*[0-9A-F]{6,};?/i' => '&nbsp;'
        ));
    }

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string|Horde_Support_Domhtml  The modified text or a Domhtml
     *                                       object if the 'return_dom'
     *                                       parameter is set.
     */
    public function postProcess($text)
    {
        try {
            $dom = new Horde_Support_Domhtml($text, $this->_params['charset']);
        } catch (Exception $e) {
            return $text;
        }

        $this->_node($dom->dom, $dom->dom);

        if (!$this->_params['return_document']) {
            $body = $dom->dom->getElementsByTagName('body')->item(0);
        }

        if ($this->_params['noprefetch']) {
            $meta = $dom->dom->createElement('meta');
            $meta->setAttribute('http-equiv', 'x-dns-prefetch-control');
            $meta->setAttribute('value-equiv', 'off');

            if ($this->_params['return_document']) {
                $dom->dom->getElementsByTagName('head')->item(0)->appendChild($meta);
            } elseif ($body) {
                $body->appendChild($meta);
            }
        }

        if ($this->_params['return_dom']) {
            return $dom;
        }

        if ($this->_params['return_document']) {
            return $dom->returnHtml();
        }

        $text = '';
        if ($body && $body->hasChildNodes()) {
            foreach ($body->childNodes as $child) {
                $text .= $dom->dom->saveXML($child);
            }
        }

        return Horde_String::convertCharset($text, $dom->encoding, $this->_params['charset']);
    }

    /**
     * Process DOM node.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMElement $node  Element node.
     *
     * @return string  The plaintext representation.
     */
    protected function _node($doc, $node)
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    switch (strtolower($child->tagName)) {
                    case 'a':
                        /* Strip out data URLs living in an A HREF element
                         * (Bug #8715). */
                        if ($child->hasAttribute('href') &&
                            preg_match("/\s*data:/i", $child->getAttribute('href'))) {
                            $child->removeAttribute('href');
                        }
                        break;

                    case 'applet':
                    case 'embed':
                    case 'iframe':
                    case 'import':
                    case 'java':
                    case 'layer':
                    case 'meta':
                    case 'object':
                    case 'script':
                    case 'xml':
                        /* Remove all tags that might cause trouble. */
                        $node->removeChild($child);
                        continue 2;

                    case 'base':
                    case 'link':
                    case 'style':
                        /* We primarily strip out <base> tags due to styling
                         * concerns. There is a security issue with HREF tags,
                         * but the 'javascript' search/replace code
                         * sufficiently filters these strings. */
                        if ($this->_params['strip_styles']) {
                            $node->removeChild($child);
                            continue 2;
                        }
                        break;

                    case 'set':
                        /* I believe this attack only works on old browsers.
                         * But makes no sense allowing HTML to try to set
                         * innerHTML anyway. */
                        if ($child->hasAttribute('attributename') &&
                            (strcasecmp($child->getAttribute('attributename'), 'innerHTML') === 0)) {
                            $node->removeChild($child);
                            continue 2;
                        }
                    }

                    $remove = $this->_params['strip_style_attributes']
                        ? array('style')
                        : array();

                    foreach ($child->attributes as $val) {
                        /* Never allow on<foo>="bar()",
                         * attribute="[mocha|*script]:foo()", or
                         * attribute="&{...}". */
                        if ((stripos(ltrim($val->name), 'on') === 0) ||
                            preg_match("/^\s*(?:mocha:|[^:]+script:|&{)/i", $val->value)) {
                            $remove[] = $val->name;
                        }
                    }

                    foreach ($remove as $val) {
                        $child->removeAttribute($val);
                    }
                } elseif ($child instanceof DOMComment) {
                    /* Remove HTML comments (including some scripts &
                     * styles). */
                    if ($this->_params['strip_styles']) {
                        $node->removeChild($child);
                        continue;
                    }
                }

                $this->_node($doc, $child);
            }
        }
    }

}
