<?php
/**
 * The Horde_Themes_Sound:: class provides an object-oriented interface to
 * a themed sound.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Themes_Sound extends Horde_Themes_Element
{
    /**
     */
    protected $_dirname = 'sounds';

    /**
     */
    public function __get($name)
    {
        /* Sounds must be in .wav format. */
        return (substr(strrchr($name, '.'), 1) == 'wav')
            ? parent::__get($name)
            : null;
    }

}
