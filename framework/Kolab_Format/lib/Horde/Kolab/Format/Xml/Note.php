<?php
/**
 * Implementation for notes in the Kolab XML format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * Kolab XML handler for note groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
class Horde_Kolab_Format_Xml_Note extends Horde_Kolab_Format_Xml
{
    /**
     * The name of the root element.
     *
     * @var string
     */
    protected $_root_name = 'note';

    /**
     * Specific data fields for the prefs object
     *
     * @var Kolab
     */
    protected $_fields_specific = array(
        'summary' => array(
            'type'    => self::TYPE_STRING,
            'value'   => self::VALUE_DEFAULT,
            'default' => '',
        ),
        'background-color' => array(
            'type'    => self::TYPE_COLOR,
            'value'   => self::VALUE_DEFAULT,
            'default' => '#000000',
        ),
        'foreground-color' => array(
            'type'    => self::TYPE_COLOR,
            'value'   => self::VALUE_DEFAULT,
            'default' => '#ffff00',
        ),
    );
}
