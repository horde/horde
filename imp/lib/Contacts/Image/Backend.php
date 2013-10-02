<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Interface for a profile image backend.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
interface IMP_Contacts_Image_Backend
{
    /**
     * Raw image data of a contact image.
     *
     * @param string $email  An email address.
     *
     * @return array  Null if image can't be generated. Otherwise, an array
     *                with these keys:
     * <pre>
     *   - data: (string) Image data.
     *   - type: (string) MIME type of the image data.
     * </pre>
     */
    public function rawImage($email);

    /**
     * URL of the contact image.
     *
     * @param string $email  An email address.
     *
     * @return Horde_Url  Null if image can't be generated. Otherwise, the URL
     *                    of the contact image.
     */
    public function urlImage($email);

}
