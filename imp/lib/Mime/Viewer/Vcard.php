<?php
/**
 * The IMP_Mime_Viewer_Vcard class renders out the contents of vCard
 * files in HTML format and allows inline display of embedded photos.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Vcard extends Horde_Core_Mime_Viewer_Vcard
{
    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - c: (integer) The VCARD component that contains an image.
     *   - p: (integer) The index of image inside the component to display.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        $vars = $GLOBALS['injector']->getInstance('Horde_Variables');

        if (!isset($vars->p)) {
            $this->_imageUrl = $this->getConfigParam('imp_contents')->urlView($this->_mimepart, 'download_render');
            return parent::_render();
        }

        /* Send the requested photo. */
        $data = $this->_mimepart->getContents();
        $ical = new Horde_Icalendar();
        if (!$ical->parsevCalendar($data, 'VCALENDAR', $this->_mimepart->getCharset())) {
            // TODO: Error reporting
            return array();
        }
        $components = $ical->getComponents();
        if (!isset($components[$vars->c])) {
            // TODO: Error reporting
            return array();
        }
        $name = $components[$vars->c]->getAttributeDefault('FN', false);
        if ($name === false) {
            $name = $components[$vars->c]->printableName();
        }
        if (empty($name)) {
            $name = preg_replace('/\..*?$/', '', $this->_mimepart->getName());
        }

        $photos = $components[$vars->c]->getAllAttributes('PHOTO');
        if (!isset($photos[$vars->p])) {
            // TODO: Error reporting
            return array();
        }
        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => base64_decode($photos[$vars->p]['value']),
                'name' => $name . '.' . Horde_Mime_Magic::mimeToExt($photos[$vars->p]['params']['TYPE']),
                'type' => $photos[$vars->p]['params']['TYPE'],
            )
        );
    }

}
