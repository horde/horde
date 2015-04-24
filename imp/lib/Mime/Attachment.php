<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Utility method to determine whether a part should be considered an
 * attachment for display purposes.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Attachment
{
    /**
     * Determines if a MIME type is an attachment.
     *
     * @param Horde_Mime_Part $part  The MIME part.
     */
    public static function isAttachment(Horde_Mime_Part $part)
    {
        $type = $part->getType();

        switch ($type) {
        case 'application/ms-tnef':
        case 'application/pgp-keys':
        case 'application/vnd.ms-tnef':
            return false;
        }

        if ($part->parent) {
            switch ($part->parent->getType()) {
            case 'multipart/encrypted':
                switch ($type) {
                case 'application/octet-stream':
                    return false;
                }
                break;

            case 'multipart/signed':
                switch ($type) {
                case 'application/pgp-signature':
                case 'application/pkcs7-signature':
                case 'application/x-pkcs7-signature':
                    return false;
                }
                break;
            }
        }

        switch ($part->getDisposition()) {
        case 'attachment':
            return true;
        }

        switch ($part->getPrimaryType()) {
        case 'application':
            if (strlen($part->getName())) {
                return true;
            }
            break;

        case 'audio':
        case 'video':
            return true;

        case 'multipart':
            return false;
        }

        return false;
    }

}
