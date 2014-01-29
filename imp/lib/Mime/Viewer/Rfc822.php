<?php
/**
 * Copyright 2009-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer that indicates that all subparts should be wrapped in a parent
 * display DIV element.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Rfc822 extends Horde_Mime_Viewer_Rfc822
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => false,
        'raw' => true
    );

    /**
     */
    protected function _render()
    {
        /* Need to display raw text from server, since this is essentially
         * a View Source action for the part and the current value of
         * $_mimepart may contain altered data (e.g. data that has been
         * content transfer decoded). */
        return $this->_renderReturn(
            $this->getConfigParam('imp_contents')->getBodyPart($this->_mimepart->getMimeId())->data,
            'text/plain; charset=' . $this->getConfigParam('charset')
        );
    }

    /**
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
    protected function _renderRaw()
    {
        /* Needed for same reason as explained in _render(). */
        return $this->_render();
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
