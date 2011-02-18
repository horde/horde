<?php
/**
 * MIME Viewer configuration for Gollem.
 *
 * Settings in this file override settings in horde/config/mime_drivers.php.
 * All drivers configured in that file, but not configured here, will also
 * be used to display MIME content.
 *
 * IMPORTANT: Local overrides should be placed in mime_drivers.local.php, or
 * mime_drivers-servername.php if the 'vhosts' setting has been enabled in
 * Horde's configuration.
 */

$mime_drivers = array(
    /* Image viewing. Gollem can display images inline. */
    'images' => array(
        'inline' => true,
        'handles' => array(
            'image/*'
        )
    )
);
