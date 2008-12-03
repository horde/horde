<?php
/**
 * Copyright 2006 Duck <duck@obala.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/news/LICENSE.
 *
 * $Id: index.php 308 2008-01-31 13:20:46Z duck $
 *
 * @author Duck <duck@obala.net>
 * @package News
 */

define('NEWS_BASE', dirname(__FILE__));
$news_configured = (is_readable(NEWS_BASE . '/config/conf.php') &&
                    is_readable(NEWS_BASE . '/config/prefs.php'));

if (!$news_configured) {
    define('HORDE_LIBS', '');
    require NEWS_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('news', NEWS_BASE,
                                   array('conf.php', 'prefs.php', 'sources.php'));
}

require NEWS_BASE . '/content.php';
