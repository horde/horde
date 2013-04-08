<?php
/**
 * Copyright 2009-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer that indicates that all subparts should be wrapped in a parent
 * display DIV element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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

    /**
     */
    protected function _getHeaderValue($ob, $header)
    {
        switch ($header) {
        case 'date':
            return $GLOBALS['injector']->getInstance('IMP_Message_Ui')->getLocalTime(new Horde_Imap_Client_DateTime($ob->getValue('date')));

        default:
            return parent::_getHeaderValue($ob, $header);
        }
    }

}
