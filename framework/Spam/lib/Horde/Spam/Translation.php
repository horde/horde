<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2016-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Translation wrapper class for Horde_Spam.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2016-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
class Horde_Spam_Translation extends Horde_Translation_Autodetect
{
    /**
     * The translation domain
     *
     * @var string
     */
    protected static $_domain = 'Horde_Spam';

    /**
     * The absolute PEAR path to the translations for the default gettext
     * handler.
     *
     * @var string
     */
    protected static $_pearDirectory = '@data_dir@';
}
