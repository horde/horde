<?php
/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API (http://www.gravatar.com/site/implement/).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar
{
    /** The default Gravatar base URL */
    const STANDARD = 'http://www.gravatar.com';

    /** The Gravatar base URL in SSL context */
    const SECURE  = 'https://secure.gravatar.com';

    /**
     * The base Gravatar URL.
     *
     * @var string
     */
    private $_base;

    /**
     * Constructor.
     *
     * The default Gravatar base URL is Horde_Service_Gravatar::STANDARD. If you
     * need URLs in an HTTPS context you should provide the base URL parameter
     * as Horde_Service_Gravatar::SECURE. In case you wish to access another URL
     * offering the Gravatar API you can specify the base URL of this service as
     * $base.
     *
     * @param string $base The base Gravatar URL.
     */
    public function __construct($base = self::STANDARD)
    {
        $this->_base = $base;
    }

    /**
     * Return the Gravatar ID for the specified mail address.
     *
     * @param string $mail The mail address.
     *
     * @return string The Gravatar ID.
     *
     * @throws InvalidArgumentException In case the mail address is no string.
     */
    public function getId($mail)
    {
        if (!is_string($mail)) {
            throw new InvalidArgumentException('The mail address must be a string!');
        }
        return md5(strtolower(trim($mail)));
    }

    /**
     * Return the Gravatar image URL for the specified mail address. The
     * returned URL can be directly used with an <img/> tag e.g. <img
     * src="http://www.gravatar.com/avatar/205e460b479e2e5b48aec07710c08d50" />
     *
     * @param string $mail The mail address.
     *
     * @return string The image URL.
     *
     * @throws InvalidArgumentException In case the mail address is no string.
     */
    public function getAvatarUrl($mail)
    {
        return $this->_base . '/avatar/' . $this->getId($mail);
    }

    /**
     * Return the Gravatar profile URL.
     *
     * @param string $mail The mail address.
     *
     * @return string The profile URL.
     *
     * @throws InvalidArgumentException In case the mail address is no string.
     */
    public function getProfileUrl($mail)
    {
        return $this->_base . '/' . $this->getId($mail);
    }
}