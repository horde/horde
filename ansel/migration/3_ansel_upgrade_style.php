<?php
require_once __DIR__ . '/../lib/Style.php';

/**
 * Upgrade to Ansel 2 style schema
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeStyle extends Horde_Db_Migration_Base
{
    public function up()
    {
        $this->changeColumn('ansel_shares', 'attribute_style', 'text');

        // Create: ansel_hashes
        $t = $this->createTable(
            'ansel_hashes',
            array('autoincrementKey' => false));
        $t->column('style_hash', 'string', array('limit' => 255));
        $t->primaryKey(array('style_hash'));
        $t->end();
        $styles = Horde::loadConfiguration('styles.php', 'styles', 'ansel');

        // Migrate existing data
        $sql = 'SELECT share_id, attribute_style FROM ansel_shares';
        $this->announce('Migrating gallery styles.', 'cli.message');
        $defaults = array(
            'thumbstyle' => 'Thumb',
            'background' => 'none',
            'gallery_view' => 'Gallery',
            'widgets' => array(
                 'Tags' => array('view' => 'gallery'),
                 'OtherGalleries' => array(),
                 'Geotag' => array(),
                 'Links' => array(),
                 'GalleryFaces' => array(),
                 'OwnerFaces' => array()));

        $rows = $this->_connection->selectAll($sql);
        $update = 'UPDATE ansel_shares SET attribute_style = ? WHERE share_id = ?;';
        foreach ($rows as $row) {
            // Make sure we haven't already migrated
            if (@unserialize($row['attribute_style']) instanceof Ansel_Style) {
                $this->announce('Skipping share ' . $row['attribute_style'] . ', already migrated.', 'cli.message');
                continue;
            }
            if (empty($styles[$row['attribute_style']])) {
                $newStyle = '';
            } else {
                $properties = array_merge($defaults, $styles[$row['attribute_style']]);

                // Translate previous generator names:
                $properties = $this->_translate_generators($properties);
                $newStyle = serialize(new Ansel_Style($properties));
            }
            $this->announce('Migrating share id: ' . $row['share_id'] . ' from: ' . $row['attribute_style'] . ' to: ' . $newStyle, 'cli.message');

            try {
                $this->_connection->execute($update, array($newStyle, $row['share_id']));
            } catch (Horde_Db_Exception $e) {
                $this->announce('ERROR: ' . $e->getMessage());
            }
        }
    }

    /**
     * Downgrade, though all style information will be lost and reverted to
     * 'ansel_default'.
     */
    public function down()
    {
        $sql = "UPDATE ansel_shares set attribute_style = 'ansel_default'";
        $this->_connection->execute($sql);
        $this->changeColumn('ansel_shares', 'attribute_style', 'string',  array('limit' => 255));
        $this->dropTable('ansel_hashes');
    }

    /**
     * Translates old style array from Ansel 1.x to Ansel 2.x.
     *
     * @param array $properties
     */
    private function _translate_generators($properties)
    {
        $thumb_map = array(
            'thumb' => 'Thumb',
            'prettythumb' => 'RoundedThumb',
            'shadowsharpthumb' => 'ShadowThumb',
            'polaroidthumb' => 'PolaroidThumb');

        // Make sure we didn't already translate
        if (!empty($thumb_map[$properties['thumbstyle']])) {
            $properties['thumbstyle'] = $thumb_map[$properties['thumbstyle']];
            unset($properties['requires_png']);
            unset($properties['name']);
            unset($properties['title']);
            unset($properties['hide']);
            unset($properties['default_galleryimage_type']);
        }
        return $properties;
    }

}