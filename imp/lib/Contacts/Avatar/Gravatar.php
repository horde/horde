<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.fsf.org/copyleft/gpl.html GPL
 * @package   IMP
 */

/**
 * Generate contact avatar image using the Gravatar service.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts_Avatar_Gravatar implements IMP_Contacts_Avatar_Backend
{
    /**
     */
    public function avatarImg($email)
    {
        if (class_exists('Horde_Service_Gravatar')) {
            $gravatar = new Horde_Service_Gravatar(
                Horde_Service_Gravatar::STANDARD,
                $GLOBALS['injector']->getInstance('Horde_Http_Client')
            );

            $data = $gravatar->fetchAvatar($email, array(
                'default' => 404,
                'size' => 80
            ));

            if (!is_null($data)) {
                rewind($data);
                $img_data = stream_get_contents($data);

                if (strlen($img_data)) {
                    return array(
                        'desc' => '',
                        'url' => Horde_Url_Data::create('image/jpeg', $img_data)
                    );
                }
            }
        }

        return null;
    }

}
