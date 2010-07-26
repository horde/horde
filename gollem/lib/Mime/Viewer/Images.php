<?php
/**
 * The Gollem_Horde_Mime_Viewer_Images class allows images to be displayed
 * inline from file data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Mime
 */
class Gollem_Horde_Mime_Viewer_Images extends Horde_Mime_Viewer_Images
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
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
     * URL parameters used by this function:
     * <pre>
     * TODO
     * </pre>
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $data = '';
        // TODO - set mimepart contents
        //$url = Horde_Util::addParameter(Horde::applicationUrl('view.php'), array('actionID' => 'download_file', 'file' => $this->mime_part->getName(), 'dir' => Horde_Util::getFormData('dir'), 'driver' => Horde_Util::getFormData('driver')));
        return parent::_render();
    }

}
