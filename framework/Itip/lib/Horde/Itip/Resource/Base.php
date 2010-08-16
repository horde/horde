<?php
/**
 * Simple information provider for an invited resource.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Simple information provider for an invited resource.
 *
 * Copyright 2010 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Resource_Base
implements Horde_Itip_Resource
{
    /**
     * The mail address.
     *
     * @var string
     */
    private $_mail;

    /**
     * The common name.
     *
     * @var string
     */
    private $_common_name;

    /**
     * Constructor.
     *
     * @param string $mail        The mail address.
     * @param string $common_name The common name.
     */
    public function __construct($mail, $common_name)
    {
        $this->_mail        = $mail;
        $this->_common_name = $common_name;
    }

    /**
     * Retrieve the mail address of the resource.
     *
     * @return string The mail address.
     */
    public function getMailAddress()
    {
        return $this->_mail;
    }

    /**
     * Retrieve the reply-to address for the resource.
     *
     * @return string The reply-to address.
     */
    public function getReplyTo()
    {
    }

    /**
     * Retrieve the common name of the resource.
     *
     * @return string The common name.
     */
    public function getCommonName()
    {
        return $this->_common_name;
    }

    /**
     * Retrieve the "From" address for this resource.
     *
     * @return string The "From" address.
     */
    public function getFrom()
    {
        return sprintf("%s <%s>", $this->_common_name, $this->_mail);
    }
}