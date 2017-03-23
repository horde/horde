<?php
/**
 * Copyright 2002-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Compress
 * @todo     Use Streams for content.
 */

/**
 * Object to parse and represent a generic file encapsulated by a TNEF file.
 *
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @category  Horde
 * @copyright 2002-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Compress
 */
class Horde_Compress_Tnef_File extends Horde_Compress_Tnef_Object
{
    /**
     *
     * @var string
     */
    public $name;

    /**
     * @mixed
     */
    public $content;

    /**
     *
     * @var mixed
     */
    public $metafile;

    /**
     * @var Horde_Compress_Tnef_Date
     */
    public $created;

    /**
     * The size of the file's contents in bytes.
     *
     * @var integer
     */
    public $size;

    /**
     * The MIME type.
     *
     * @string
     */
    public $type;

    /**
     * The MIME subtype
     *
     * @var string
     */
    public $subtype;

    public function toArray()
    {
        return array(
            'type' => $this->type,
            'subtype' => $this->subtype,
            'name' => $this->name,
            'size' => $this->size,
            'stream' => $this->content);
    }

    public function setTnefAttribute($attribute, $value, $size)
    {
        $this->_logger->debug(sprintf(
            'TNEF: Horde_Compress_Tnef_File::setTnefAttribute(0x%X, <value>, %n)',
            $attribute, $size)
        );
        switch ($attribute) {
        case Horde_Compress_Tnef::AFILENAME:
            $this->name = preg_replace('/.*[\/](.*)$/', '\1', $value);
            break;

        case Horde_Compress_Tnef::ATTACHDATA:
            $this->content = $value;
            $this->size = $size;
            break;

        case Horde_Compress_Tnef::ATTACHMETAFILE:
            $this->metafile = $value;
            break;

        case Horde_Compress_Tnef::ATTACHCREATEDATE:
            $this->created = new Horde_Compress_Tnef_Date($value);
            break;
        }
    }

    public function setMapiAttribute($type, $name, $value)
    {
        switch ($name) {
        case Horde_Compress_Tnef::MAPI_ATTACH_LONG_FILENAME:
            $this->name = preg_replace('/.*[\/](.*)$/', '\1', $value);
            break;

        case Horde_Compress_Tnef::MAPI_ATTACH_MIME_TAG:
            $type = str_replace("\0", '', preg_replace('/^(.*)\/.*/', '\1', $value));
            $subtype = str_replace("\0", '', preg_replace('/.*\/(.*)$/', '\1', $value));
            $this->type = $type;
            $this->subtype = $subtype;
            break;

        case Horde_Compress_Tnef::MAPI_ATTACH_EXTENSION:
            $value = Horde_Mime_Magic::extToMime($value);
            $type = str_replace("\0", '', preg_replace('/^(.*)\/.*/', '\1', $value));
            $subtype = str_replace("\0", '', preg_replace('/.*\/(.*)$/', '\1', $value));
            $this->type = $type;
            $this->subtype = $subtype;
        }
    }

}