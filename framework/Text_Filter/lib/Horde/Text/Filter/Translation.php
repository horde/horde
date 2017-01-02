<?php
/**
 * @package Text_Filter
 *
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * Horde_Text_Filter_Translation is the translation wrapper class for Horde_Text_Filter.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Text_Filter
 */
class Horde_Text_Filter_Translation extends Horde_Translation_Autodetect
{
    /**
     * The translation domain
     *
     * @var string
     */
    protected static $_domain = 'Horde_Text_Filter';

    /**
     * The absolute PEAR path to the translations for the default gettext handler.
     *
     * @var string
     */
    protected static $_pearDirectory = '@data_dir@';
}
