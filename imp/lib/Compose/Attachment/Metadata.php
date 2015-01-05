<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Metadata information for attachments.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl
 * @package   IMP
 *
 * @property array $data  Raw data.
 */
class IMP_Compose_Attachment_Metadata
{
    /**
     * Metadata.
     *
     * @var array
     */
    protected $_data = array();

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'data':
            return array_filter($this->_data);
        }

        return null;
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'data':
            $this->_data = $value;
            break;
        }
    }

}
