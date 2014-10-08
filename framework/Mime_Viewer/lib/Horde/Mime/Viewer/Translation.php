<?php
/**
 * @package Mime_Viewer
 *
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * Horde_Mime_Viewer_Translation is the translation wrapper class for Horde_Mime_Viewer.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Mime_Viewer
 */
class Horde_Mime_Viewer_Translation extends Horde_Translation_Autodetect
{
    /**
     * The translation domain
     *
     * @var string
     */
    protected static $_domain = 'Horde_Mime_Viewer';

    /**
     * The absolute PEAR path to the translations for the default gettext handler.
     *
     * @var string
     */
    protected static $_pearDirectory = '@data_dir@';
}
