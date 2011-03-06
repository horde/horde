<?php
/**
 * The class Horde_SyncMl_Command_SyncElement stores information from the
 * <Add>, <Delete> and <Replace> elements found inside a <Sync> command.
 *
 * Instances of this class are created during the XML parsing by
 * Horde_SyncMl_Command_Sync.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_SyncElement
{
    /**
     * The MIME content type of the sync command.
     *
     * @var string
     */
    public $contentType;

    /**
     * Encoding format of the content as specified in the <Meta><Format>
     * element, like 'b64'.
     *
     * @var string
     */
    public $contentFormat;

    /**
     * The actual data content of the sync command.
     *
     * @var string $content
     */
    public $content = '';

    /**
     * The size of the data item of the sync command in bytes as specified by
     * a <Size> element.
     *
     * @var integer
     */
    public $size;

    /**
     * The command ID (<CmdID>) of the sync command.
     *
     * @var integer
     */
    public $cmdID;

    /**
     * Name of the sync command, like 'Add'.
     *
     * @var string
     */
    public $elementType;

    /**
     * The client ID for the data item processed in the sync command.
     *
     * @var string
     */
    public $cuid;

    /**
     * The code to be sent as status response in a <Status> element, one of
     * the Horde_SycnMl::RESPONSE_* constants.
     *
     * This is set in Horde_SyncMl_Sync::handleClientSyncItem() when "processing"
     * the item.
     *
     * @var integer
     */
    public $responseCode;

    /**
     * The Sync object for this element is part of.
     *
     * @var object Horde_SyncMl_Sync
     */
    public $sync;

    /**
     * Constructor.
     *
     * @param Horde_SyncMl_Sync $sync
     * @param string $elementType
     * @param integer $cmdID
     * @param integer $size
     */
    public function __construct($sync, $elementType, $cmdID, $size)
    {
        $this->sync = $sync;
        $this->elementType = $elementType;
        $this->cmdID = $cmdID;
        $this->size = $size;
    }
}
