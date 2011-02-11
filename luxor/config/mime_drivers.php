<?php
/**
 * MIME Viewer configuration for Luxor.
 *
 * Settings in this file override settings in horde/config/mime_drivers.php.
 * All drivers configured in that file, but not configured here, will also
 * be used to display MIME content.
 *
 * IMPORTANT: Local overrides should be placed in mime_drivers.local.php, or
 * mime_drivers-servername.php if the 'vhosts' setting has been enabled in
 * Horde's configuration.
 */

/* By default, Luxor uses the default Horde-wide settings contained in
 * horde/config/mime_drivers.php. */

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/mime_drivers.local.php')) {
    include dirname(__FILE__) . '/mime_drivers.local.php';
}
