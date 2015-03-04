<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attachment data for an outgoing compose message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read boolean $linked  Should this attachment be linked?
 * @property-read Horde_Url $link_url  The URL, if the attachment is linked.
 * @property-read IMP_Compose_Attachment_Storage $storage  The storage object.
 * @property-read string $tmpfile  The temporary file location on the local
 *                                 filesystem.
 */
class IMP_Compose_Attachment implements Serializable
{
    /**
     * Force this attachment to be linked?
     *
     * @var boolean
     */
    public $forceLinked = false;

    /**
     * Attachment ID.
     *
     * @var integer
     */
    public $id;

    /**
     * Is this part associated with multipart/related data?
     *
     * @var boolean
     */
    public $related = false;

    /**
     * Compose object cache ID.
     *
     * @var string
     */
    protected $_composeCache;

    /**
     * Does the part contain the attachment contents?
     *
     * @var boolean
     */
    protected $_isBuilt = false;

    /**
     * Should this attachment be linked?
     *
     * @var boolean
     */
    protected $_linked = null;

    /**
     * MIME part object.
     *
     * @var Horde_Mime_Part
     */
    protected $_part;

    /**
     * The unique identifier for the file.
     *
     * @var string
     */
    protected $_uuid = null;

    /**
     * Constructor.
     *
     * @param IMP_Compose $ob        Compose object.
     * @param Horde_Mime_Part $part  MIME part object.
     * @param string $tmp_file       Temporary filename containing the data.
     */
    public function __construct(IMP_Compose $ob, Horde_Mime_Part $part,
                                $tmp_file)
    {
        $this->id = ++$ob->atcId;
        $this->_composeCache = strval($ob);
        $this->_part = $part;
        $this->_uuid = strval(new Horde_Support_Uuid());

        $storage = $this->storage;
        $storage->write($tmp_file, $this->getPart());
        /* Need to save this information now, since it is possible that
         * storage backends change their linked status based on the data
         * written to the backend. */
        $this->_linked = $storage->linked;
    }

    /**
     */
    public function __get($name)
    {
        global $injector;

        switch ($name) {
        case 'linked':
            return ($this->forceLinked || ($this->_linked === true));

        case 'link_url':
            return $this->storage->link_url;

        case 'storage':
            $linked = $this->linked
                ? 'linked'
                : (is_null($this->_linked) ? null : 'atc');
            return $injector->getInstance('IMP_Factory_ComposeAtc')->create(
                null,
                $this->_uuid,
                $linked
            );

        case 'tmpfile':
            return $this->storage->getTempFile();
        }
    }

    /**
     * Return the MIME part object.
     *
     * @param boolean $build  If true, ensures the part contains the data.
     *
     * @return Horde_Mime_Part  MIME part object.
     * @throws IMP_Compose_Exception
     */
    public function getPart($build = false)
    {
        if ($build && !$this->_isBuilt) {
            $this->_part->setContents(
                $this->storage->read()->stream,
                array('stream' => true)
            );
            $this->_isBuilt = true;
        }

        return $this->_part;
    }

    /**
     * Delete the attachment data.
     */
    public function delete()
    {
        if (!$this->linked) {
            try {
                $this->storage->delete();
            } catch (Exception $e) {}
        }
    }

    /**
     * Get a URL of the data.
     *
     * @return Horde_Url  URL to display the attachment data.
     */
    public function viewUrl()
    {
        return Horde::url('view.php', true)->add(array(
            'actionID' => 'compose_attach_preview',
            'composeCache' => strval($GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create($this->_composeCache)),
            'id' => $this->id
        ));
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        /* Don't store Mime_Part data. Can't use clone here ATM, since there
         * appears to be a PHP bug. Since this is an object specific to IMP
         * (and we are only using in a certain predictable way), it should
         * be ok to directly alter the MIME part object without any ill
         * effects. */
        $this->_part->clearContents();
        $this->_isBuilt = false;

        return $GLOBALS['injector']->getInstance('Horde_Pack')->pack(
            array(
                $this->_composeCache,
                $this->id,
                $this->_linked,
                $this->_part,
                $this->related,
                $this->_uuid
            ), array(
                'compression' => false,
                'phpob' => true
            )
        );
    }

    /**
     */
    public function unserialize($data)
    {
        list(
            $this->_composeCache,
            $this->id,
            $this->_linked,
            $this->_part,
            $this->related,
            $this->_uuid
        ) = $GLOBALS['injector']->getInstance('Horde_Pack')->unpack($data);
    }

}
