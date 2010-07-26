<?php
/**
 * The Horde_Mime_Viewer_Msword class renders out Microsoft Word documents
 * in HTML format by using the AbiWord package.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Msword extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        /* Check to make sure the viewer program exists. */
        if (!isset($this->_conf['location']) ||
            !file_exists($this->_conf['location'])) {
            return array();
        }

        $tmp_in = Horde::getTempFile('msword');
        $tmp_out = Horde::getTempFile('msword');

        file_put_contents($tmp_in, $this->_mimepart->getContents());
        $args = ' --to=html --to-name=' . $tmp_out . ' ' . $tmp_in;

        exec($this->_conf['location'] . $args);

        if (file_exists($tmp_output)) {
            $data = file_get_contents($tmp_output);
            $type = 'text/html';
        } else {
            $data = _("Unable to translate this Word document");
            $type = 'text/plain';
        }

        return $this->_renderReturn(
            $data,
            $type . '; charset=' . $GLOBALS['registry']->getCharset()
        );
    }

}
