<?php
/**
 * The Horde_SyncMl_PropertyParameter class is used to define a single
 * parameter of a property of a data item supported by the device.
 *
 * The contents of a property parameter can be defined by an enumeration of
 * valid values (ValEnum) or by a DataType/Size combination, or not at all.
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
class Horde_SyncMl_PropertyParameter
{
    /**
     * The supported enumerated values of the content type property.
     *
     * The supported values stored in the keys of the hash, e.g. 'PUBLIC' and
     * 'PIVATE' for a text/calendar 'CLASS' property.
     *
     * @var array
     */
    public $ValEnum;

    /**
     * The datatype of the content type property, e.g. 'chr', 'int', 'bool',
     * etc.
     *
     * @var string
     */
    public $DataType;

    /**
     * The size of the content type property in bytes.
     *
     * @var integer
     */
    public $Size;

    /**
     * The display name of the content type property.
     *
     * @var string
     */
    public $DisplayName;
}
