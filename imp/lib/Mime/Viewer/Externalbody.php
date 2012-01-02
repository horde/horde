<?php
/**
 * This class handles message/external-body (RFC 2046 [5.2.3]) parts.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Externalbody extends Horde_Mime_Viewer_Base
{
    /**
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => true,
        'forceinline' => true
    );

    /**
     */
    protected function _getEmbeddedMimeParts()
    {
        switch ($this->_mimepart->getContentTypeParameter('access-type')) {
        case 'anon-ftp':
        case 'ftp':
        case 'local-file':
        case 'mail-server':
        case 'tftp':
            // RFC 2046 [5.2.3.1]: Unsupported.
            break;

        case 'content-id':
            // RFC 1873
            $imp_contents = $this->getConfigParam('imp_contents');
            $base_part = $imp_contents->getMIMEMessage();
            $cid = $this->_mimepart->getContentId();

            foreach (array_keys($base_part->contentTypeMap(true)) as $key) {
                if (($part = $base_part->getPart($key)) &&
                    ($part->getContentId() == $cid) &&
                    ($part->getType() != 'message/external-body')) {
                    $full_part = clone $imp_contents->getMIMEPart($key);
                    $full_part->setMimeId($this->_mimepart->getMimeId());
                    // TODO: Add headers from referring body part.
                    return $full_part;
                }
            }
            break;
        }

        return null;
    }

}
