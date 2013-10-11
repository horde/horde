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
     * Return a URL object representing the contact image.
     *
     * @return Horde_Url|Horde_Url_Data  URL object
     *
     * @throws IMP_Exception
     */
    public function getUrlOb()
    {
        global $conf;

        if (!empty($conf['contactsimage']['backends'])) {
            foreach ($conf['contactsimage']['backends'] as $val) {
                if (class_exists($val)) {
                    $backend = new $val();
                    if (($url = $backend->rawImage($this->_email)) ||
                        ($url = $backend->urlImage($this->_email))) {
                        return $url;
                    }
                }
            }
        }

        throw new IMP_Exception('No backend found to generate contact image.');
    }

}
