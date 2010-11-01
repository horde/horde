<?php
/**
 * This class provides the Ansel configuration for the test script.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Ansel
 */
class Ansel_Test extends Horde_Test
{
    /**
     * The module list
     *
     * @var array
     */
    protected $_moduleList = array(
        'facedetect' => array(
            'descrip' => 'Facedetect Face Detection Library',
            'required' => false,
            'error' => 'Ansel can make use of the Facedetect PHP extension for automatically detecting human faces in images.'
        ),
        'gd' => array(
            'descrip' => 'GD Support',
            'error' => 'You need either GD2 support in PHP, or an external driver like ImageMagick.  Either recompile PHP with GD2 support, or make sure that the path to ImageMagick\'s convert utility is set in horde/config/conf.php.'
        ),
        'imagick' => array(
            'descrip' => 'Imagick Library',
            'required' => false,
            'error' => 'Ansel can make use of the Imagick Library, if it is installed on your system.  It is highly recommended to use either ImageMagick\'s convert utility or the Imagick php library for faster results.'
        ),
        'libpuzzle' => array(
            'descrip' => 'Puzzle Library',
            'required' => false,
            'error' => 'Ansel can make use of the libpuzzle PHP extension for finding similar images based on image content.'
        ),
        'zip' => array(
            'descrip' => 'Zip Support',
            'required' => false,
            'error' => 'Ansel can make use of PHP\'s Zip extension for more efficiently processing uploaded ZIP files.'
        )
    );

    /**
     * PHP settings list.
     *
     * @var array
     */
    protected $_settingsList = array();

    /**
     * PEAR modules list.
     *
     * @var array
     */
    protected $_pearList = array(
        'MDB2' => array(
            'required' => true,
            'error' => 'You do not have the MDB2 package installed on your system. In addition to this package, you will need the appropriate MDB2_Driver package for your database backend.'
        )
    );

    /**
     * Required configuration files.
     *
     * @var array
     */
    protected $_fileList = array(
        'config/conf.php' => null,
        'config/prefs.php' => null
    );

    /**
     * Inter-Horde application dependencies.
     *
     * @var array
     */
    protected $_appList = array(
        'agora' => array(
            'error' => 'Agora provides the ability for users to comment on images.',
            'version' => '1.0'
        )
    );

    /**
     * Any application specific tests that need to be done.
     *
     * @return string  HTML output.
     */
    public function appTests()
    {
    }

}
