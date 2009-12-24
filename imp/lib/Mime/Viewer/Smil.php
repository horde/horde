<?php
/**
 * The IMP_Horde_Mime_Viewer_Smil renders SMIL documents to very basic HTML.
 *
 * Copyright 2006-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_Smil extends Horde_Mime_Viewer_Smil
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
                $this->_content .= '<img src="' . $this->_params['contents']->urlView($rp, 'view_attach') . '" alt="" /><br />';
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
        if (isset($this->_params['related_id']) &&
            (($key = array_search(trim($cid, '<>', $this->_params['related_cids']))) !== false)) {
            return $this->_param['contents']->getMIMEPart($key);
        }

        return false;
    }
}
