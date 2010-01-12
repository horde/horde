<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */
// Will redirect to login page if not authenticated.
require_once dirname(__FILE__) . '/lib/Application.php';
new Shout_Application();

// Load initial page as defined by view mode & preferences.
require 'extensions.php';
