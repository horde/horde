<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Viewport data object.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 *
 * @property-read string $cacheid  The cache ID.
 * @property array $data  The data array.
 * @property boolean $data_reset  True if viewport data should be reset.
 * @property array $disappear  The list of UIDs that have disappeared.
 * @property string $label  The view label.
 * @property object $metadata  Metadata.
 * @property boolean $metadata_reset  True if metadata should be reset.
 * @property array $rowlist  The rowlist array.
 * @property boolean $rowlist_reset  True if rowlist data should be reset.
 * @property integer $rownum  The row number of the provided UID.
 * @property integer $totalrows  The total number of rows in the view.
 * @property-read string $view  The view ID.
 */
class IMP_Ajax_Application_Viewport
{
    /**
     * Data.
     *
     * @var object
     */
    private $_data;

    /**
     * View object.
     *
     * @var IMP_Mailbox
     */
    private $_mbox;

    /**
     * Metadata.
     *
     * @var array
     */
    private $_metadata = array();

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mbox  Viewport view.
     */
    public function __construct(IMP_Mailbox $mbox)
    {
        $this->_data = new stdClass;
        $this->_mbox = $mbox;
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'cacheid':
            return $this->_mbox->cacheid_date;

        case 'data':
        case 'data_reset':
        case 'disappear':
        case 'label':
        case 'metadata_reset':
        case 'rowlist':
        case 'rowlist_reset':
        case 'rownum':
        case 'totalrows':
            return isset($this->_data->$name)
                ? $this->_data->$name
                : false;

       case 'metadata':
           return (object)$this->_metadata;

        case 'view':
            return $this->_mbox->form_to;
        }
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'data_reset':
        case 'metadata_reset':
        case 'rowlist_reset':
            $this->_data->$name = (bool)$value;
            break;

        case 'data':
        case 'disappear':
        case 'rowlist':
            $this->_data->$name = $value;
            break;

        case 'label':
            $this->_data->$name = strval($value);
            break;

        case 'rownum':
        case 'totalrows':
            $this->_data->$name = intval($value);
            break;
        }
    }

    /**
     * Set a metadata element.
     *
     * @param string $name   Metadata name.
     * @param string $value  Metadata value.
     */
    public function setMetadata($name, $value)
    {
        $this->_metadata[$name] = $value;
    }

    /**
     * Prepare the object used by the ViewPort javascript class.
     *
     * @return object  The ViewPort object.
     */
    public function toObject()
    {
        $ob = clone $this->_data;
        $ob->cacheid = $this->cacheid;
        $ob->view = $this->view;

        if (!empty($this->_metadata)) {
            $ob->metadata = $this->metadata;
        }

        return $ob;
    }

    /**
     * Add flag metadata to output.
     */
    public function addFlagMetadata()
    {
        global $injector;

        $flaglist = $injector->getInstance('IMP_Flags')->getList(array(
            'imap' => true,
            'mailbox' => $this->_mbox->search ? null : $this->_mbox
        ));

        $flags = array();
        foreach ($flaglist as $val) {
            $flags[] = $val->imapflag;
        }

        $this->setMetadata('flags', $flags);
    }

}
