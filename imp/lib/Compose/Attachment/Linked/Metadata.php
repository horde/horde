<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Metadata information for linked attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl
 * @package   IMP
 *
 * @property array $data  Raw data.
 * @property string $dtoken  Delete token.
 * @property string $filename  Filename.
 * @property integer $time  Timestamp.
 * @property string $type  MIME type.
 */
class IMP_Compose_Attachment_Linked_Metadata
extends IMP_Compose_Attachment_Metadata
{
    /**
     * Mapping from array keys -> property names.
     *
     * @var array
     */
    protected $_map = array(
        'd' => 'dtoken',
        'f' => 'filename',
        'm' => 'type',
        't' => 'time'
    );

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'dtoken':
        case 'filename':
        case 'type':
            $key = array_search($name, $this->_map);
            return isset($this->_data[$key])
                ? $this->_data[$key]
                : null;

        case 'time':
            $key = array_search($name, $this->_map);
            return isset($this->_data[$key])
                ? $this->_data[$key]
                : 0;
        }

        return parent::__get($name);
    }

    /**
     */
    public function __set($name, $value)
    {
        if (($key = array_search($name, $this->_map)) !== false) {
            $this->_data[$key] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

}
