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
 * Renderer to output information on:
 *   1. The duration of the video data, if that information was provided in
 *      the original message.
 *   2. Output thumbnails of the video file.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
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
        'inline' => true,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - imp_video_view: (string) One of the following:
     *     - view_thumbnail: Create thumbnail and display.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        switch ($GLOBALS['injector']->getInstance('Horde_Variables')->imp_video_view) {
        case 'view_thumbnail':
            /* Create thumbnail and display. */
            return $this->_thumbnail();

        default:
            return parent::_render();
        }
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $status = array();

        $mime_id = $this->_mimepart->getMimeId();
        $headers = Horde_Mime_Headers::parseHeaders($this->getConfigParam('imp_contents')->getBodyPart($mime_id, array(
            'length' => 0,
            'mimeheaders' => true,
            'stream' => true
        ))->data);

        if (!($duration = $headers['Content-Duration'])) {
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

            $status[] = sprintf(_("This video file is reported to be %s in length."), implode(' ', $text));
        }

        if ($this->_thumbnailBinary()) {
            $status[] = _("This is a thumbnail of a video attachment.");
            $status[] = $this->getConfigParam('imp_contents')->linkViewJS(
                $this->_mimepart,
                'view_attach',
                '<img src="' . $this->getConfigParam('imp_contents')->urlView($this->_mimepart, 'view_attach', array('params' => array('imp_video_view' => 'view_thumbnail'))) . '" />',
                null,
                null,
                null
            );
        }

        if (empty($status)) {
            return array();
        }

        $s = new IMP_Mime_Status($this->_mimepart, $status);
        $s->icon('mime/video.png');

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => '',
                'status' => $s,
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Generate thumbnail for the video.
     *
     * @return array  See parent::render().
     */
    protected function _thumbnail()
    {
        if (!($ffmpeg = $this->_thumbnailBinary())) {
            return array();
        }

        $process = proc_open(
            escapeshellcmd($ffmpeg) . ' -i pipe:0 -vframes 1 -an -ss 1 -s 240x180 -f mjpeg pipe:1',
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w')
            ),
            $pipes
        );

        $out = null;
        if (is_resource($process)) {
            fwrite($pipes[0], $this->_mimepart->getContents());
            fclose($pipes[0]);

            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        if (strlen($out)) {
            $type = 'image/jpeg';
        } else {
            $type = 'image/png';
            $img_ob = Horde_Themes::img('mini-error.png', 'imp');
            $out = file_get_contents($img_ob->fs);
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $out,
                'type' => 'image/jpeg'
            )
        );
    }

    /**
     * Get the ffmpeg binary.
     *
     * @return mixed  The binary location, or false if not available.
     */
    protected function _thumbnailBinary()
    {
        return ($this->getConfigParam('thumbnails') &&
                ($ffmpeg = $this->getConfigParam('ffmpeg')) &&
                is_executable($ffmpeg))
            ? $ffmpeg
            : false;
    }

}
