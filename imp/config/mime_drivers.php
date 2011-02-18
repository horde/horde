<?php
/**
 * MIME Viewer configuration for IMP.
 *
 * Settings in this file override settings in horde/config/mime_drivers.php.
 * All drivers configured in that file, but not configured here, will also
 * be used to display MIME content.
 *
 * IMPORTANT: Local overrides should be placed in mime_drivers.local.php, or
 * mime_drivers-servername.php if the 'vhosts' setting has been enabled in
 * Horde's configuration.
 *
 * Additional settings for IMP:
 * + If you want to limit the display of message data inline for large
 *   messages of a certain type, add a 'limit_inline_size' parameter to the
 *   desired mime type to the maximum size of the displayed message in bytes
 *   (see example under text/plain below).  If set, the user will only be able
 *   to download the part.  Don't set the parameter, or set to 0, to disable
 *   this check.
 */

$mime_drivers = array(
    /* Plain text viewer. */
    'plain' => array(
        'inline' => true,
        'handles' => array(
            'application/pgp',
            'text/plain',
            'text/rfc822-headers'
        ),

        /* If you want to limit the display of message data inline for large
         * messages, set the maximum size of the displayed message here (in
         * bytes).  If exceeded, the user will only be able to download the
         * part. Set to 0 to disable this check. */
        'limit_inline_size' => 1048576,

        /* If you want to scan ALL incoming text/plain messages for UUencoded
         * data, set the following to true. This is very performance intensive
         * and can take a long time for large messages. It is not recommended
         * (as UUencoded data is rare these days) and is disabled by
         * default. */
        'uudecode' => false
    ),

    /* HTML driver settings */
    'html' => array(
        /* NOTE: Inline HTML display is turned OFF by default. */
        'inline' => false,
        'handles' => array(
            'text/html'
        ),
        'icons' => array(
            'default' => 'html.png'
        ),

        /* If you want to limit the display of message data inline for large
         * messages, set the maximum size of the displayed message here (in
         * bytes).  If exceeded, the user will only be able to download the
         * part. Set to 0 to disable this check. */
        'limit_inline_size' => 1048576,

        /* Check for phishing exploits? */
        'phishing_check' => true
    ),

    /* Default smil driver. */
    'smil' => array(
        'inline' => true,
        'handles' => array(
            'application/smil'
        )
    ),

    /* Image display. */
    'images' => array(
        'inline' => true,
        'handles' => array(
            'image/*'
        ),

        /* Display thumbnails for all images, not just large images? */
        'allthumbs' => true,

        /* Display images inline that are less than this size (in bytes). */
        'inlinesize' => 262144,

        /* A list of from e-mail addresses that are considered "safe" for
         * purposes of image blocking (if image blocking is enabled in the
         * preferences). */
        'safe_addrs' => array()
    ),

    /* Enriched text display. */
    'enriched' => array(
        'inline' => true,
        'handles' => array(
            'text/enriched'
        ),
        'icons' => array(
            'default' => 'text.png'
        )
    ),

    /* PDF display. */
    'pdf' => array(
        'handles' => array(
            'application/pdf',
            'application/x-pdf',
            'image/pdf'
        ),
        'icons' => array(
            'default' => 'pdf.png'
        )
    ),

    /* PGP (Pretty Good Privacy) display. */
    'pgp' => array(
        'inline' => true,
        'handles' => array(
            'application/pgp-encrypted',
            'application/pgp-keys',
            'application/pgp-signature'
        ),
        'icons' => array(
            'default' => 'encryption.png'
        )
    ),

    /* S/MIME display. */
    'smime' => array(
        'inline' => true,
        'handles' => array(
            'application/x-pkcs7-signature',
            'application/x-pkcs7-mime',
            'application/pkcs7-signature',
            'application/pkcs7-mime'
        ),
        'icons' => array(
            'default' => 'encryption.png'
        )
    ),

    /* vCard display. */
    'vcard' => array(
        'handles' => array(
            'text/directory',
            'text/vcard',
            'text/x-vcard'
        ),
        'icons' => array(
            'default' => 'vcard.png'
        )
    ),

    /* Zip file display. */
    'zip' => array(
        'handles' => array(
            'application/x-compressed',
            'application/x-zip-compressed',
            'application/zip'
        ),
        'icons' => array(
            'default' => 'compressed.png'
        )
    ),

    /* Delivery status messages display. */
    'status' => array(
        'inline' => true,
        'handles' => array(
            'message/delivery-status'
        )
    ),

    /* Message Disposition Notification (MDN) display. */
    'mdn' => array(
        'inline' => true,
        'handles' => array(
            'message/disposition-notification'
        )
    ),

    /* Appledouble message display. */
    'appledouble' => array(
        'inline' => true,
        'handles' => array(
            'multipart/appledouble'
        ),
        'icons' => array(
            'default' => 'apple.png'
        )
    ),

    /* ITIP (iCalendar Transport-Independent Interoperability Protocol)
     * display. */
    'itip' => array(
        'inline' => true,
        'handles' => array(
            'text/calendar',
            'text/x-vcalendar'
        ),
        'icons' => array(
            'default' => 'itip.png'
        )
    ),

    /* Alternative part display.
     * YOU SHOULD NOT NORMALLY ALTER THIS SETTING. */
    'alternative' => array(
        'inline' => true,
        'handles' => array(
            'multipart/alternative'
        )
    ),

    /* Related part display.
     * YOU SHOULD NOT NORMALLY ALTER THIS SETTING. */
    'related' => array(
        'inline' => true,
        'handles' => array(
            'multipart/related'
        ),
        'icons' => array(
            'default' => 'html.png'
        )
    ),

    /* Partial parts display.
     * YOU SHOULD NOT NORMALLY ALTER THIS SETTING. */
    'partial' => array(
        'handles' => array(
            'message/partial'
        )
    ),

    /* Digest message (RFC 2046 [5.2.1]) display.
     * YOU SHOULD NOT NORMALLY ALTER THIS SETTING. */
    'rfc822' => array(
        'handles' => array(
            'message/rfc822',
            'x-extension/eml'
        )
    )
);
