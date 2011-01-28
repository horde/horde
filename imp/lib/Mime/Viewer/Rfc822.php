<?php
/**
 * The IMP_Mime_Viewer_Rfc822 class extends the base Horde Mime Viewer
 * by indicating that all subparts should be wrapped in a display DIV.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Rfc822 extends Horde_Mime_Viewer_Rfc822
{
    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $ret = parent::_renderInfo();
        if (!empty($ret)) {
            $ret[$this->_mimepart->getMimeId()]['wrap'] = 'mimePartWrap';
        }
        return $ret;
    }

}
