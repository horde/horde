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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Kolab XML handler for note groupware objects.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Xml_Note extends Horde_Kolab_Format_Xml
{
    /**
     * Specific data fields for the note object
     *
     * @var Kolab
     */
    protected $_fields_specific;

    /**
     * Constructor
     */
    public function __construct($parser, $params = array())
    {
        $this->_root_name = 'note';

        /** Specific note fields, in kolab format specification order
         */
        $this->_fields_specific = array(
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

        parent::__construct($parser, $params);
    }
}
