<?php
/**
 * Copyright 2006-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for SMIL documents to very basic HTML.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Smil extends Horde_Mime_Viewer_Smil
{
    /**
     * User-defined function callback for start elements.
     *
     * @param object $parser  Handle to the parser instance (not used).
     * @param string $name    The name of this XML element.
     * @param array $attrs    List of this element's attributes.
     */
    protected function _startElement($parser, $name, $attrs)
    {
        switch ($name) {
        case 'IMG':
            if (isset($attrs['SRC']) &&
                (($rp = $this->_getRelatedLink($attrs['SRC'])) !== false)) {
                $this->_content .= '<img src="' . $this->getConfigParam('imp_contents')->urlView($rp, 'view_attach', array('params' => array('imp_img_view' => 'data'))) . '" /><br />';
            }
            break;

        case 'TEXT':
            if (isset($attrs['SRC']) &&
                (($rp = $this->_getRelatedLink($attrs['SRC'])) !== false)) {
                $this->_content .= htmlspecialchars($rp->getContents()) . '<br />';
            }
            break;
        }
    }

    /**
     * Get related parts.
     *
     * @param string $cid  The CID to search for.
     *
     * @return mixed  Either the related MIME_Part or false.
     */
    protected function _getRelatedLink($cid)
    {
        $imp_contents = $this->getConfigParam('imp_contents');

        return (($related_part = $imp_contents->findMimeType($this->_mimepart->getMimeId(), 'multipart/related')) &&
                (($key = $related_part->getMetadata('related_ob')->cidSearch($cid)) !== false))
            ? $imp_contents->getMimePart($key)
            : false;
    }

}
