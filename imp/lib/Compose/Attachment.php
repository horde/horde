<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */

/**
 * Attachment data for an outgoing compose message.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   IMP
 */
class IMP_Compose_Attachment implements Serializable
{
    /* The virtual path to use for VFS data. */
    const VFS_ATTACH_PATH = '.horde/imp/compose';

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
     * The VFS filename.
     *
     * @var string
     */
    public $vfsname = null;

    /**
     * Does the part contain the attachment contents?
     *
     * @var boolean
     */
    protected $_isBuilt = false;

    /**
     * MIME part object.
     *
     * @var Horde_Mime_Part
     */
    protected $_part;

    /**
     * Temporary filename.
     *
     * @var string
     */
    protected $_tmpfile = null;

    /**
     * Constructor.
     *
     * @param integer $id            The attachment ID.
     * @param Horde_Mime_Part $part  MIME part object.
     * @param string $tmp_file       Temporary filename containing the data.
     */
    public function __construct($id, Horde_Mime_Part $part, $tmp_file)
    {
        $this->id = $id;
        $this->_part = $part;
        $this->_tmpfile = $tmp_file;
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
            if (!is_null($this->_tmpfile)) {
                $data = fopen($this->_tmpfile, 'r');
                $stream = true;
            } else {
                try {
                    $vfs = $GLOBALS['injector']->getInstance('IMP_ComposeVfs');
                    if (method_exists($vfs, 'readStream')) {
                        $data = $vfs->readStream(self::VFS_ATTACH_PATH, $this->vfsname);
                        $stream = true;
                    } else {
                        $data = $vfs->read(self::VFS_ATTACH_PATH, $this->vfsname);
                        $stream = false;
                    }
                } catch (Horde_Vfs_Exception $e) {
                    throw new IMP_Compose_Exception($e);
                }
            }

            $this->_part->setContents($data, array('stream' => $stream));
            $this->_isBuilt = true;
        }

        return $this->_part;
    }

    /**
     * Delete the attachment data.
     */
    public function delete()
    {
        $this->_tmpfile = null;

        if (!is_null($this->vfsname)) {
            try {
                $GLOBALS['injector']->getInstance('IMP_ComposeVfs')->deleteFile(self::VFS_ATTACH_PATH, $this->vfsname);
            } catch (Horde_Vfs_Exception $e) {}
            $this->vfsname = null;
        }
    }

    /**
     * Get a URL of the data.
     *
     * @param IMP_Compose $ob  The compose object containing this attachment.
     *
     * @return Horde_Url  URL to display the attachment data.
     */
    public function viewUrl(IMP_Compose $ob)
    {
        return Horde::url('view.php')->add(array(
            'actionID' => 'compose_attach_preview',
            'composeCache' => strval($ob),
            'id' => $this->id
        ));
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        /* Don't store Mime_Part data. Can't use clone here ATTM, since there
         * appears to be a PHP bug. Since this is an object specific to IMP
         * (and we are only using in a certain predictable way), it should
         * be ok to directly alter the MIME part object without any ill
         * effects. */
        $this->_part->clearContents();
        $this->_isBuilt = false;

        if (!is_null($this->_tmpfile)) {
            try {
                $this->vfsname = strval(new Horde_Support_Randomid());
                $GLOBALS['injector']->getInstance('IMP_ComposeVfs')->write(self::VFS_ATTACH_PATH, $this->vfsname, $this->_tmpfile, true);
            } catch (Horde_Vfs_Exception $e) {
                throw new IMP_Compose_Exception($e);
            }

            $this->_tmpfile = null;
        }

        return serialize(array(
            'i' => $this->id,
            'p' => $this->_part,
            'r' => $this->related,
            'v' => $this->vfsname
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);

        $this->id = $data['i'];
        $this->_part = $data['p'];
        $this->related = !empty($data['r']);
        $this->vfsname = $data['v'];
    }

}
