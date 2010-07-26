<?php
/**
 * The Horde_Mime_Viewer_Webcpp class renders out various content in HTML
 * format by using Web C Plus Plus.
 *
 * Web C Plus plus: http://webcpp.sourceforge.net/
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Webcpp extends Horde_Mime_Viewer_Driver
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
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_toHTML();

        /* The first 2 lines are the Content-Type line and a blank line so
         * remove them before outputting. */
        reset($ret);
        $ret[key($ret)]['data'] = preg_replace("/.*\n.*\n/", '', $ret[key($ret)]['data'], 1);

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $ret = $this->_toHTML();
        reset($ret);
        $data = $ret[key($ret)]['data'];

        /* Extract the style sheet, removing any global body formatting
         * if we're displaying inline. */
        $res = preg_split(';(</style>)|(<style type="text/css">);', $data);
        $style = $res[1];
        $style = preg_replace('/\nbody\s+?{.*?}/s', '', $style);

        /* Extract the content. */
        $res = preg_split('/\<\/?pre\>/', $data);
        $body = $res[1];

        $ret[key($ret)]['data'] = '<style>' . $style . '</style><div class="webcpp" style="white-space:pre;font-family:Lucida Console,Courier,monospace;">' . $body . '</div>';

        return $ret;
    }

    /**
     * Converts the code to HTML.
     *
     * @return string  The HTML-ified version of the MIME part contents.
     */
    protected function _toHTML()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        /* Create temporary files for Webcpp. */
        $tmpin  = Horde::getTempFile('WebcppIn');
        $tmpout = Horde::getTempFile('WebcppOut');

        /* Write the contents of our buffer to the temporary input file. */
        file_put_contents($tmpin, $this->_mimepart->getContents());

        /* Get the extension for the mime type. */
        $ext = Horde_Mime_Magic::MIMEToExt($this->_mimepart->getType());

        /* Execute Web C Plus Plus. Specifying the in and out files didn't
         * work for me but pipes did. */
        exec($this->_conf['location'] . " --pipe --pipe -x=$ext -l -a -t < $tmpin > $tmpout");
        $results = file_get_contents($tmpout);

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $results,
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }
}
