<?php
/**
 * The Horde_Mime_Viewer_Richtext class renders out HTML text from
 * text/richtext content tags, (RFC 1896 [7.1.3]).
 *
 * A minimal richtext implementation is one that simply converts "<lt>" to
 * "<", converts CRLFs to SPACE, converts <nl> to a newline according to
 * local newline convention, removes everything between a <comment> command
 * and the next balancing </comment> command, and removes all other
 * formatting commands (all text enclosed in angle brackets).
 *
 * We implement the following tags:
 *   <bold>, <italic>, <fixed>, <smaller>, <bigger>, <underline>, <center>,
 *   <flushleft>, <flushright>, <indent>, <subscript>, <excerpt>, <paragraph>,
 *   <signature>, <comment>, <no-op>, <lt>, <nl>
 *
 * The following tags are implemented differently than described in the RFC
 * (due to limitations in HTML output):
 *   <heading> - Output as centered, bold text.
 *   <footing> - Output as centered, bold text.
 *   <np>      - Output as paragraph break.
 *
 * The following tags are NOT implemented:
 *   <indentright>, <outdent>, <outdentright>, <samepage>, <iso-8859-X>,
 *   <us-ascii>,
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Richtext extends Horde_Mime_Viewer_Base
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
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderFullReturn($this->_renderReturn(
            $this->_toHTML(),
            'text/html; charset=' . $this->_mimepart->getCharset()

        ));
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        return $this->_renderReturn(
            Horde_String::convertCharset($this->_toHTML(), $this->_mimepart->getCharset(), 'UTF-8'),
            'text/html; charset=UTF-8'
        );
    }

    /**
     * Convert richtext to HTML.
     *
     * @return string  The converted HTML string.
     */
    protected function _toHTML()
    {
        $text = trim($this->_mimepart->getContents());
        if ($text == '') {
            return array();
        }

        /* We add space at the beginning and end of the string as it will
         * make some regular expression checks later much easier (so we
         * don't have to worry about start/end of line characters). */
        $text = ' ' . $text . ' ';

        /* Remove everything between <comment> tags. */
        $text = preg_replace('/<comment.*>.*<\/comment>/Uis', '', $text);

        /* Remove any unrecognized tags in the text.  We don't need <no-op>
         * in $tags since it doesn't do anything anyway.  All <comment> tags
         * have already been removed. */
        $tags = '<bold><italic><fixed><smaller><bigger><underline><center><flushleft><flushright><indent><subscript><excerpt><paragraph><signature><lt><nl>';
        $text = strip_tags($text, $tags);

        /* <lt> becomes a '<'. CRLF becomes a SPACE. */
        $text = str_ireplace(array('<lt>', "\r\n"), array('&lt;', ' '), $text);

        /* We try to protect against bad stuff here. */
        $text = @htmlspecialchars($text, ENT_QUOTES, $this->_mimepart->getCharset());

        /* <nl> becomes a newline (<br />);
         * <np> becomes a paragraph break (<p />). */
        $text = str_ireplace(array('&lt;nl&gt;', '&lt;np&gt;'), array('<br />', '<p />'), $text);

        /* Now convert the known tags to html. Try to remove any tag
         * parameters to stop people from trying to pull a fast one. */
        $replace = array(
            '/(?<!&lt;)&lt;bold.*&gt;(.*)&lt;\/bold&gt;/Uis' => '<span style="font-weight: bold">\1</span>',
            '/(?<!&lt;)&lt;italic.*&gt;(.*)&lt;\/italic&gt;/Uis' => '<span style="font-style: italic">\1</span>',
            '/(?<!&lt;)&lt;fixed.*&gt;(.*)&lt;\/fixed&gt;/Uis' => '<font face="fixed">\1</font>',
            '/(?<!&lt;)&lt;smaller.*&gt;(.*)&lt;\/smaller&gt;/Uis' => '<span style="font-size: smaller">\1</span>',
            '/(?<!&lt;)&lt;bigger.*&gt;(.*)&lt;\/bigger&gt;/Uis' => '<span style="font-size: larger">\1</span>',
            '/(?<!&lt;)&lt;underline.*&gt;(.*)&lt;\/underline&gt;/Uis' => '<span style="text-decoration: underline">\1</span>',
            '/(?<!&lt;)&lt;center.*&gt;(.*)&lt;\/center&gt;/Uis' => '<div align="center">\1</div>',
            '/(?<!&lt;)&lt;flushleft.*&gt;(.*)&lt;\/flushleft&gt;/Uis' => '<div align="left">\1</div>',
            '/(?<!&lt;)&lt;flushright.*&gt;(.*)&lt;\/flushright&gt;/Uis' => '<div align="right">\1</div>',
            '/(?<!&lt;)&lt;indent.*&gt;(.*)&lt;\/indent&gt;/Uis' => '<blockquote>\1</blockquote>',
            '/(?<!&lt;)&lt;excerpt.*&gt;(.*)&lt;\/excerpt&gt;/Uis' => '<cite>\1</cite>',
            '/(?<!&lt;)&lt;subscript.*&gt;(.*)&lt;\/subscript&gt;/Uis' => '<sub>\1</sub>',
            '/(?<!&lt;)&lt;superscript.*&gt;(.*)&lt;\/superscript&gt;/Uis' => '<sup>\1</sup>',
            '/(?<!&lt;)&lt;heading.*&gt;(.*)&lt;\/heading&gt;/Uis' => '<br /><div align="center" style="font-weight: bold">\1</div><br />',
            '/(?<!&lt;)&lt;footing.*&gt;(.*)&lt;\/footing&gt;/Uis' => '<br /><div align="center" style="font-weight: bold">\1</div><br />',
            '/(?<!&lt;)&lt;paragraph.*&gt;(.*)&lt;\/paragraph&gt;/Uis' => '<p>\1</p>',
            '/(?<!&lt;)&lt;signature.*&gt;(.*)&lt;\/signature&gt;/Uis' => '<address>\1</address>'
        );
        $text = preg_replace(array_keys($replace), array_values($replace), $text);

        /* Now we remove the leading/trailing space we added at the start. */
        $text = substr($text, 1, -1);

        /* Wordwrap. */
        $text = str_replace(array("\t", '  ', "\n "), array('        ', ' &nbsp;', "\n&nbsp;"), $text);
        if ($text[0] == ' ') {
            $text = '&nbsp;' . substr($text, 1);
        }

        return '<p style="font-size:100%;font-family:Lucida Console,Courier,Courier New;">' . nl2br($text) . '</p>';
    }

}
