<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Extension of base audio driver by outputting information on the duration
 * of the audio data if that information was provided in the original message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Audio extends Horde_Mime_Viewer_Audio
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
        'raw' => false
    );

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $mime_id = $this->_mimepart->getMimeId();
        $headers = Horde_Mime_Headers::parseHeaders($this->getConfigParam('imp_contents')->getBodyPart($mime_id, array(
            'length' => 0,
            'mimeheaders' => true,
            'stream' => true
        ))->data);

        if (($duration = $headers->getValue('content-duration')) === null) {
            return array();
        }

        $text = array();

        if ($minutes = floor($duration / 60)) {
            $text[] = sprintf(
                ngettext(_("%d minute"), _("%d minutes"), $minutes),
                $minutes
            );
        }

        if ($seconds = ($duration % 60)) {
            $text[] = sprintf(
                ngettext(_("%d second"), _("%d seconds"), $seconds),
                $seconds
            );
        }

        $status = new IMP_Mime_Status(
            sprintf(_("This audio file is reported to be %s in length."), implode(' ', $text))
        );
        $status->icon('mime/audio.png');

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => $status,
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

}
