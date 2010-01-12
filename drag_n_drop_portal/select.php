<?php
/**
 * $Id: select.php 215 2008-01-10 19:32:18Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
define('HORDE_BASE', dirname(__FILE__) . '/..');
require_once HORDE_BASE . '/lib/base.php';
require_once 'Horde/Loader.php';

?>
<form>
<select id="block_column">
<option value="0"><?php echo _("First column") ?></option>
<option value="1"><?php echo _("Second column") ?></option>
<option value="2"><?php echo _("Third column") ?></option>
</select>
<br />
<select id="block_selection" size="7">
<?php
$collection = Horde_Block_Collection::singleton();
foreach ($collection->getBlocksList() as $id => $name) {
    echo '<option value="' . $id  .'">' . $name . '</option>';
}
?>
</select>
<br />
<input type="button" class="button" value="<?php echo _("Add") ?>" onclick="return addWidget()" />
<input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="return cancelRedBox()" />
</form>
