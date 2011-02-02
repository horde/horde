#!/usr/bin/env php
<?php
/**
 * Fix MDB2 sequence names. Thie script changes the MDB2 sequence names from the
 * default of 'sequence' to 'id' to be consistent with all horde apps, and to
 * allow automated upgrade scripts to use MDB2_Schema in the future.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 */

/* These are the table => key names that need to be updated */
$to_upgrade = array('ansel_shares' => 'share_id',
                    'ansel_images' => 'image_id',
                    'ansel_tags' => 'tag_id',
                    'ansel_faces' => 'face_id',
                    'genie_shares' => 'share_id',
                    'ingo_shares' => 'share_id',
                    'klutz_comics' => 'comicpic_id',
                    'kronolith_shares' => 'share_id',
                    'mnemo_shares' => 'share_id',
                    'nag_shares' => 'share_id',
                    'turba_shares' => 'share_id',
                    'whups_shares' => 'share_id');

/* Set up the CLI environment */
require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('cli' => true));

$config = $GLOBALS['conf']['sql'];
unset($config['charset']);
$schema = MDB2_Schema::factory($config, array('seqcol_name' => 'id'));
if (is_a($schema, 'PEAR_Error')) {
    $cli->fatal($schema->getMessage());
}
$db = MDB2::factory($config);
if (is_a($db, 'PEAR_Error')) {
    $cli->fatal($db->getMessage());
}

if (is_a($result = $db->loadModule('Manager'), 'PEAR_Error')) {
    $cli->fatal($result->getMessage());
}
$tables = $db->manager->listTables();
if (is_a($tables, 'PEAR_Error')) {
    $cli->fatal($tables->getMessage());
}

/* Update any of the tables that we have */
foreach ($to_upgrade as $table => $field) {
    if (array_search($table, $tables) !== false) {
        $results = $schema->createSequence(
            $table, array('on' => array('table' => $table, 'field' => $field)), true);

        if (is_a($results, 'PEAR_Error')) {
            $cli->fatal(sprintf('Unable to modify the sequence for %s: %s', $table, $results->getMessage()));
        }
        $cli->message(sprintf('Modified sequence for %s', $table), 'cli.success');
    }
}

$cli->message('Done', 'cli.success');
