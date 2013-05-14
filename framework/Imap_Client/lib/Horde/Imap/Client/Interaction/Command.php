<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * An object representing an IMAP command (RFC 3501 [2.2.1]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 * @since     2.10.0
 */
class Horde_Imap_Client_Interaction_Command extends Horde_Imap_Client_Data_Format_List
{
    /**
     * Debug string to use instead of command text.
     *
     * @var string
     */
    public $debug = null;

    /**
     * Use LITERAL+ if available
     *
     * @var boolean
     */
    public $literalplus = true;

    /**
     * Server response.
     *
     * @var Horde_Imap_Client_Interaction_Server
     */
    public $response;

    /**
     * The command tag.
     *
     * @var string
     */
    public $tag;

    /**
     * Constructor.
     *
     * @param string $cmd  The IMAP command.
     * @param string $tag  The tag to use. If not set, will be automatically
     *                     generated.
     */
    public function __construct($cmd, $tag = null)
    {
        $this->tag = is_null($tag)
            ? substr(new Horde_Support_Randomid(), 0, 10)
            : strval($tag);

        parent::__construct($this->tag);

        $this->add($cmd);
    }

    /**
     * Get the command.
     *
     * @return string  The command.
     */
    public function getCommand()
    {
        return $this->_data[1];
    }

}
