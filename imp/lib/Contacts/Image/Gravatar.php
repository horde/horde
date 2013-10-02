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
 * Generate contact image using the Gravatar service.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts_Image_Gravatar implements IMP_Contacts_Image_Backend
{
    /**
     */
    public function rawImage($email)
    {
        if (class_exists('Horde_Service_Gravatar')) {
            $gravatar = new Horde_Service_Gravatar();
            $data = $gravatar->fetchAvatar($email, array(
                'default' => 404,
                'size' => 80
            ));
            rewind($data);
            $img_data = stream_get_contents($data);

            if (strlen($img_data)) {
                return Horde_Url_Data::create(
                    'image/jpeg',
                    $img_data
                );
            }
        }

        return null;
    }

    /**
     */
    public function urlImage($email)
    {
        return null;
    }

}
