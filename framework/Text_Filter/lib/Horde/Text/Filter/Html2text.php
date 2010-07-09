<?php
/**
 * Takes HTML and converts it to formatted, plain text.
 *
 * Parameters:
 * <pre>
 * charset - (string) The charset to use for html_entity_decode() calls.
 * width - (integer) The wrapping width. Set to 0 to not wrap.
 * </pre>
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
class Horde_Text_Filter_Html2text extends Horde_Text_Filter_Base
{
    /**
     * The list of links contained in the message.
     *
     * @var array
     */
    protected $_linkList = array();

    /**
     * Current list indentation level.
     *
     * @var integer
     */
    protected $_indent = 0;

    /**
     * Current blockquote level.
     *
     * @var integer
     */
    protected $_bqlevel = 0;

    /**
     * Current blockquote data.
     *
     * @var array
     */
    protected $_bqdata = array();

    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'UTF-8',
        'width' => 75
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $replace = array(
            "\r" => '',
            "\n" => ' ',
            "\t" => ' '
        );

        return array(
            'replace' => $replace
        );
    }

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        $this->_bqlevel = $this->_indent = 0;
        $this->_bqdata = $this->_linkList = array();

        return $text;
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
        if (extension_loaded('dom')) {
            $text = Horde_String::convertCharset($text, $this->_params['charset'], 'UTF-8');

            $old_error = libxml_use_internal_errors(true);
            $doc = DOMDocument::loadHTML('<?xml encoding="UTF-8">' . $text);
            if ($old_error) {
                libxml_use_internal_errors(false);
            }

            $text = Horde_String::convertCharset($this->_node($doc, $doc), 'UTF-8', $this->_params['charset']);
        }

        /* Bring down number of empty lines to 2 max, and remove trailing
         * ws. */
        $text = preg_replace(array("/\s*\n{3,}/", "/ +\n/"), array("\n\n", "\n"), $text);

        /* Wrap the text to a readable format. */
        if ($this->_params['width']) {
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

        return ltrim(rtrim($text), "\n");
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
        $out = '';

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    switch (strtolower($child->tagName)) {
                    case 'h1':
                    case 'h2':
                    case 'h3':
                        $out .= "\n\n" .
                            strtoupper($this->_node($doc, $child)) .
                            "\n\n";
                        break;

                    case 'h4':
                    case 'h5':
                    case 'h6':
                        $out .= "\n\n" .
                            ucwords($this->_node($doc, $child))
                            . "\n\n";
                        break;

                    case 'b':
                    case 'strong':
                        $out .= strtoupper($this->_node($doc, $child));
                        break;

                    case 'u':
                        $out .= '_' . $this->_node($doc, $child) . '_';
                        break;

                    case 'em':
                    case 'i':
                        $out .= '/' . $this->_node($doc, $child) . '/';
                        break;

                    case 'hr':
                        $out .= "\n-------------------------\n";
                        break;

                    case 'ol':
                    case 'ul':
                        ++$this->_indent;
                        $out .= "\n\n" . $this->_node($doc, $child) . "\n\n";
                        --$this->_indent;
                        break;

                    case 'p':
                        if ($tmp = $this->_node($doc, $child)) {
                            $out .= "\n" . $tmp . "\n";
                        }
                        break;

                    case 'table':
                        if ($tmp = $this->_node($doc, $child)) {
                            $out .= "\n\n" . $tmp . "\n\n";
                        }
                        break;

                    case 'tr':
                        $out .= "\n  " . rtrim($this->_node($doc, $child));
                        break;

                    case 'th':
                        $out .= strtoupper($this->_node($doc, $child)) . " \t";
                        break;

                    case 'td':
                        $out .= $this->_node($doc, $child) . " \t";
                        break;

                    case 'li':
                        $out .= "\n" . str_repeat('  ', $this->_indent) . '* ' . $this->_node($doc, $child);
                        break;

                    case 'a':
                        $out .= $this->_node($doc, $child) . $this->_buildLinkList($doc, $child);
                        break;

                    case 'blockquote':
                        if ($this->_bqlevel) {
                            $this->_bqdata[] = array(
                                'level' => $this->_bqlevel,
                                'text' => $out
                            );
                            $out = '';
                        }
                        ++$this->_bqlevel;
                        $this->_bqdata[] = array(
                            'level' => $this->_bqlevel,
                            'text' => $this->_node($doc, $child)
                        );
                        --$this->_bqlevel;

                        if (!$this->_bqlevel) {
                            $out .= "\n\n";
                            foreach ($this->_bqdata as $val) {
                                if (empty($val['text'])) {
                                    continue;
                                }

                                if ($this->_params['width']) {
                                    $tmp = array();
                                    foreach (explode("\n", $val['text']) as $val2) {
                                        $tmp = array_merge($tmp, explode("\n", wordwrap($val2, $this->_params['width'] - (2 * $val['level']))));
                                    }
                                } else {
                                    $tmp = array($val['text']);
                                }

                                /* Clean out empty entries. */
                                for ($i = 0, $cnt = count($tmp); $i < $cnt; ++$i) {
                                    if (!empty($tmp[$i])) {
                                        break;
                                    }
                                    unset($tmp[$i]);
                                }

                                $tmp = array_values($tmp);

                                for ($i = count($tmp); $i >= 0; --$i) {
                                    if (!empty($tmp[$i])) {
                                        break;
                                    }
                                    unset($tmp[$i]);
                                }

                                foreach ($tmp as $val2) {
                                    $out .= str_repeat("> ", $val['level']) . rtrim($val2) . "\n";
                                }
                            }
                            $out .= "\n\n";
                            $this->_bqdata = array();
                        }
                        break;

                    case 'div':
                        $out .= $this->_node($doc, $child) . "\n";
                        break;

                    case 'br':
                        $out .= "\n";
                        break;

                    default:
                        $out .= $this->_node($doc, $child);
                        break;
                    }
                } elseif ((get_class($child) == 'DOMText') &&
                          !$child->isWhitespaceInElementContent()) {
                    $tmp = $child->textContent;
                    if ($child->parentNode->tagName == 'body' ||
                        !$child->previousSibling) {
                        $tmp = ltrim($tmp);
                    }
                    if (!$child->nextSibling) {
                        $tmp = rtrim($tmp);
                    }
                    $out .= html_entity_decode($tmp, ENT_QUOTES, 'UTF-8');
                }
            }
        }

        return $out;
    }

    /**
     * Maintains an internal list of links to be displayed at the end
     * of the text, with numeric indices to the original point in the
     * text they appeared.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMElement $node  Element node.
     */
    protected function _buildLinkList($doc, $node)
    {
        $link = $node->getAttribute('href');
        $display = $node->textContent;

        $parsed_link = parse_url($link);
        $parsed_display = parse_url($display);

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
            return '';
        }

        if (($pos = array_search($link, $this->_linkList)) === false) {
            $this->_linkList[] = $link;
            $pos = count($this->_linkList) - 1;
        }

        return '[' . ($pos + 1) . ']';
    }

}
