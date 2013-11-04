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
 * Generates a contact image to use for a given e-mail address.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contacts_Image
{
    /* Image types. */
    const AVATAR = 1;
    const FLAG = 2;

    /**
     * The e-mail address.
     *
     * @var string
     */
    protected $_email;

    /**
     * Constructor.
     *
     * @param string $email  The e-mail address.
     */
    public function __construct($email)
    {
        $this->_email = $email;
    }

    /**
     * Return the data representing the contact image.
     *
     * @param integer $type  The image type.
     *
     * @return array  Array with the following keys:
     * <pre>
     *   - desc: (string) Description.
     *   - url: (Horde_Url|Horde_Url_Data) URL object.
     * </pre>
     *
     * @throws IMP_Exception
     */
    public function getImage($type)
    {
        global $conf;

        if (!empty($conf['contactsimage']['backends'])) {
            switch ($type) {
            case self::AVATAR:
                $func = 'avatarImg';
                $type = 'IMP_Contacts_Avatar_Backend';
                break;

            case self::FLAG:
                $func = 'flagImg';
                $type = 'IMP_Contacts_Flag_Backend';
                break;
            }

            foreach ($conf['contactsimage']['backends'] as $val) {
                if (class_exists($val)) {
                    $backend = new $val();
                    if (($backend instanceof $type) &&
                        ($url = $backend->$func($this->_email))) {
                        return $url;
                    }
                }
            }
        }

        throw new IMP_Exception('No backend found to generate contact image.');
    }

}
