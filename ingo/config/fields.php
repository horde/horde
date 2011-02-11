<?php
/**
 * This file defines the set of default match items to display when creating
 * a new filter rule.
 *
 * IMPORTANT: Local overrides should be placed in fields.local.php, or
 * fields-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 *
 * These fields will only appear if the driver can handle it.
 *
 * Users will have to manually insert the name of the header on the rule
 * creation screen if it does not appear in this list.
 *
 * Format of $ingo_fields array:
 * 'LABEL' => array(
 *     MANDATORY:
 *     'label' => (string)  The gettext label for the entry.
 *     'type'  => (integer) The type of test. Either:
 *                          Ingo_Storage::TYPE_HEADER  --  Header test
 *                          Ingo_Storage::TYPE_SIZE    --  Message size test
 *                          Ingo_Storage::TYPE_BODY    --  Body test
 *     OPTIONAL:
 *     'tests' => (array)   Force these tests to be used only.
 *                          If not set, will use the fields generally
 *                          available to the driver.
 * )
 */
$ingo_fields = array(
    'To' => array(
        'label' => _("To"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Subject' => array(
        'label' => _("Subject"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Sender' => array(
        'label' => _("Sender"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'From' => array(
        'label' => _("From"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Cc' => array(
        'label' => _("Cc"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Bcc' => array(
        'label' => _("Bcc"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Resent-from' => array(
        'label' => _("Resent-From"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Resent-to' => array(
        'label' => _("Resent-To"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'List-Id' => array(
        'label' => _("List-ID"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Received' => array(
        'label' => _("Received"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'X-Spam-Level' => array(
        'label' => _("X-Spam-Level"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'X-Spam-Score' => array(
        'label' => _("X-Spam-Score"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'X-Spam-Status' => array(
        'label' => _("X-Spam-Status"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'X-Priority' => array(
        'label' => _("X-Priority"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'To,Cc,Bcc,Resent-to' => array(
        'label' => _("Destination (To, Cc, Bcc, etc.)"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'From,Sender,Reply-to,Resent-from' => array(
        'label' => _("Source (From, Reply-to, etc.)"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'To,Cc,Bcc,Resent-to,From,Sender,Reply-to,Resent-from' => array(
        'label' => _("Participant (From, To, etc.)"),
        'type' => Ingo_Storage::TYPE_HEADER
    ),
    'Size' => array(
        'label' => _("Size"),
        'type' => Ingo_Storage::TYPE_SIZE,
        'tests' => array('greater than', 'less than')
    ),
    'Body' => array(
        'label' => _("Body"),
        'type' => Ingo_Storage::TYPE_BODY,
        'tests' => array(
            'contains', 'not contain', 'is', 'not is', 'begins with',
            'not begins with', 'ends with', 'not ends with', 'regex',
            'matches', 'not matches'
        )
    )
);

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/fields.local.php')) {
    include dirname(__FILE__) . '/fields.local.php';
}
