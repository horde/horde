<?php
/**
 * This class outputs information on the duration of the video data, if that
 * information was provided in the original message.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Video extends Horde_Mime_Viewer_Default
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
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
            'mimeheaders' => true
        )));

        if (($duration = $headers->getValue('content-duration')) === null) {
            return array();
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => array(
                    array(
                        'icon' => Horde::img('mime/video.png'),
                        'text' => array(
                            sprintf(_("This video file is reported to be %d minutes, %d seconds in length."), floor($duration / 60), $duration % 60)
                        )
                    )
                ),
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

}
