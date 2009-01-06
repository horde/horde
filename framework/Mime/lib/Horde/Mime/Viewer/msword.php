<?php
/**
 * The Horde_Mime_Viewer_msword class renders out Microsoft Word documents
 * in HTML format by using the wvWare package.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_msword extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => false,
        'full' => true,
        'info' => false,
        'inline' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        $charset = NLS::getCharset();
        $tmp_word = Horde::getTempFile('msword');
        $tmp_output = Horde::getTempFile('msword');
        $tmp_dir = Horde::getTempDir();
        $tmp_file = str_replace($tmp_dir . '/', '', $tmp_output);

        if (OS_WINDOWS) {
            $args = ' -x ' . dirname($this->_conf['location']) . "\\wvHtml.xml -d $tmp_dir -1 $tmp_word > $tmp_output";
        } else {
            $version = exec($this->_conf['location'] . ' --version');
            $args = (version_compare($version, '0.7.0') >= 0)
                ? " --charset=$charset --targetdir=$tmp_dir $tmp_word $tmp_file"
                : " $tmp_word $tmp_output";
        }

        $fh = fopen($tmp_word, 'w');
        fwrite($fh, $this->_mimepart->getContents());
        fclose($fh);

        exec($this->_conf['location'] . $args);

        if (file_exists($tmp_output)) {
            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => file_get_contents($tmp_output),
                    'status' => array(),
                    'type' => 'text/html; charset=' . $charset
                )
            );
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => _("Unable to translate this Word document"),
                'status' => array(),
                'type' => 'text/plain; charset=' . $charset
            )
        );
    }
}
