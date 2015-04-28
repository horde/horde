<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/**
 * Horde_Service_Gravatar abstracts communication with Services supporting the
 * Gravatar API.
 *
 * @category Horde
 * @package  Service_Gravatar
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.gravatar.com/site/implement/
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
     * The HTTP client to access the server.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * Constructor.
     *
     * The default Gravatar base URL is Horde_Service_Gravatar::STANDARD. If
     * you need URLs in an HTTPS context you should provide the base URL
     * parameter as Horde_Service_Gravatar::SECURE. In case you wish to access
     * another URL offering the Gravatar API you can specify the base URL of
     * this service as $base.
     *
     * @param string            $base    The base Gravatar URL.
     * @param Horde_Http_Client $client  The HTTP client to access the server.
     */
    public function __construct(
        $base = self::STANDARD,
        Horde_Http_Client $client = null
    )
    {
        $this->_base   = $base;
        if ($client === null) {
            $client = new Horde_Http_Client();
        }
        $this->_client = $client;
    }

    /**
     * Return the Gravatar ID for the specified mail address.
     *
     * @param string $mail  The mail address.
     *
     * @return string  The Gravatar ID.
     */
    public function getId($mail)
    {
        if (!is_string($mail)) {
            throw new InvalidArgumentException('The mail address must be a string!');
        }
        return md5(Horde_String::lower(trim($mail)));
    }

    /**
     * Return the Gravatar image URL for the specified mail address. The
     * returned URL can be directly used with an IMG tag e.g.:
     * &lt;img src="http://www.gravatar.com/avatar/hash" /&gt;
     *
     * @param string $mail  The mail address.
     * @param mixed $opts   Additional options. If an integer, treated as the
     *                      'size' option.  If an array, the following options
     *                      are available:
     * <pre>
     *   - default: (string) Default behavior. Valid values are '404', 'mm',
     *              'identicon', 'monsterid', 'wavatar', 'retro', 'blank', or
     *              a URL-encoded URL to use as the default image.
     *   - rating: (string) Rating. Valid values are 'g', 'pg', 'r', and 'x'.
     *   - size: (integer) Image size. Valid values are between 1 and 512.
     * </pre>
     *
     * @return Horde_Url  The image URL.
     */
    public function getAvatarUrl($mail, $opts = array())
    {
        if (is_integer($opts)) {
            $opts = array('size' => $opts);
        }

        if (!empty($opts['size']) &&
            (($opts['size'] < 1) || ($opts['size'] > 512))) {
            throw InvalidArgumentException('The size parameter is out of bounds');
        }

        $url = new Horde_Url($this->_base . '/avatar/' . $this->getId($mail));
        if (!empty($opts['default'])) {
            $url->add('d', $opts['default']);
        }
        if (!empty($opts['rating'])) {
            $url->add('r', $opts['rating']);
        }
        if (!empty($opts['size'])) {
            $url->add('s', $opts['size']);
        }

        return $url;
    }

    /**
     * Return the Gravatar profile URL.
     *
     * @param string $mail  The mail address.
     *
     * @return string  The profile URL.
     */
    public function getProfileUrl($mail)
    {
        return $this->_base . '/' . $this->getId($mail);
    }

    /**
     * Fetch the Gravatar profile information.
     *
     * @param string $mail  The mail address.
     *
     * @return string  The profile information.
     */
    public function fetchProfile($mail)
    {
        return $this->_client->get($this->getProfileUrl($mail) . '.json')
            ->getBody();
    }

    /**
     * Return the Gravatar profile information as an array.
     *
     * @param string $mail  The mail address.
     *
     * @return array  The profile information.
     */
    public function getProfile($mail)
    {
        return json_decode($this->fetchProfile($mail), true);
    }

    /**
     * Fetch the avatar image.
     *
     * @param string $mail  The mail address.
     * @param mixed $opts   Additional options. See getAvatarUrl().
     *
     * @return resource  The image as stream resource, or null if the server
     *                   returned an error.
     */
    public function fetchAvatar($mail, $opts = array())
    {
        $get = $this->_client->get($this->getAvatarUrl($mail, $opts));
        return ($get->code == 404)
            ? null
            : $get->getStream();
    }

}
