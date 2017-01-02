<?php
/**
 * @package Kolab_Storage
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * Horde_Kolab_Storage_Translation is the translation wrapper class for Horde_Kolab_Storage.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_Translation extends Horde_Translation_Autodetect
{
    /**
     * The translation domain
     *
     * @var string
     */
    protected static $_domain = 'Horde_Kolab_Storage';

    /**
     * The absolute PEAR path to the translations for the default gettext handler.
     *
     * @var string
     */
    protected static $_pearDirectory = '@data_dir@';
}
