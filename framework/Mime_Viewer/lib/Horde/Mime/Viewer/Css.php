<?php
/**
 * The Horde_Mime_Viewer_Css class renders CSS source as HTML with an effort
 * to remove potentially malicious code.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Css extends Horde_Mime_Viewer_Source
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * Attribute preg patterns.
     *
     * @var array
     */
    protected $_attrPatterns = array(
        // Attributes
        '!([-\w]+\s*):!s' => '<span class="attr"">\\1</span>:',
        // Values
        '!:(\s*)(.+?)(\s*;)!s' => ':\\1<span class="value">\\2</span><span class="eol">\\3</span>',
        // URLs
        '!(url\([\'"]?)(.*?)([\'"]?\))!s' => '<span class="url">\\1<span class="file">\\2</span>\\3</span>',
        // Colors
        '!(#[[:xdigit:]]{3,6})!s' => '<span class="color">\\1</span>',
        // Parentheses
        '!({|})!s' => '<span class="parentheses">\\1</span>',
        // Unity
        '!(em|px|%)\b!s' => '<em>\\1</em>'
    );

    /**
     * Handles preg patterns.
     *
     * @var array
     */
    protected $_handlesPatterns = array(
        // HTML Tags
        '!\b(body|h\d|a|span|div|acronym|small|strong|em|pre|ul|ol|li|p)\b!s' => '<span class="htag">\\1</span>\\2',
        // IDs
        '!(#[-\w]+)!s' => '<span class="id">\\1</span>',
        // Class
        '!(\.[-\w]+)\b!s' => '<span class="class">\\1</span>',
        // METAs
        '!(:link|:visited|:hover|:active|:first-letter)!s' => '<span class="metac">\\1</span>'
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();

        // Need Horde headers for CSS tags.
        reset($ret);
        Horde::startBuffer();
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-header.inc';
        echo $ret[key($ret)]['data'];
        require $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc';
        $ret[key($ret)]['data'] = Horde::endBuffer();

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $css = preg_replace_callback('!(}|\*/).*?({|/\*)!s', array($this, '_handles'), htmlspecialchars($this->_mimepart->getContents(), ENT_NOQUOTES));
        $css = preg_replace_callback('!{[^}]*}!s', array($this, '_attributes'), $css);
        $css = preg_replace_callback('!/\*.*?\*/!s', array($this, '_comments'), $css);
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $this->_lineNumber(trim($css)),
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

    /**
     * TODO
     */
    protected function _comments($matches)
    {
        return '<span class="comment">' .
            preg_replace('!(http://[/\w-.]+)!s', '<a href="\\1">\\1</a>', $matches[0]) .
            '</span>';
    }

    /**
     * TODO
     */
    protected function _attributes($matches)
    {
        return preg_replace(array_keys($this->_attrPatterns), array_values($this->_attrPatterns), $matches[0]);
    }

    /**
     * TODO
     */
    protected function _handles($matches)
    {
        return preg_replace(array_keys($this->_handlesPatterns), array_values($this->_handlesPatterns), $matches[0]);
    }
}
