<?php
    $cache = IMP_Application::$prefsCache['sourceselect'];
    $imagedir = $registry->getImageDir('horde');
?>
<?php if (!empty($cache['readSelect']) || !empty($cache['prefSelect'])): ?>
<div>
 <input type="hidden" name="search_sources" id="search_sources" value="<?php echo implode("\t", $cache['search']['sources']) ?>" />
<?php if (count($cache['readable']) > 1): ?>
 <p><?php echo _("Choose the order of address books to search when expanding addresses.") ?></p>
 <table>
  <tr>
   <td>
    <label for="unselected_search_sources" class="hidden"><?php echo _("Available Address books:") ?></label>
    <select id="unselected_search_sources" name="unselected_search_sources" id="unselected_search_sources" multiple="multiple" size="5" style="width:20em">
     <option value=""><?php echo _("Available Address books:") ?></option>
<?php foreach ($cache['readSelect'] as $val): ?>
     <option value="<?php echo $val ?>"><?php echo $cache['readable'][$val] ?></option>
<?php endforeach; ?>
    </select>
   </td>
   <td>
    <a href="#" id="addsource"><?php echo Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'lhand.png' : 'rhand.png', _("Add source"), null, $imagedir) ?></a>
    <br />
    <a href="#" id="removesource"><?php echo Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'rhand.png' : 'lhand.png', _("Remove source"), null, $imagedir) ?></a>
   </td>
   <td>
    <label for="selected_search_sources" class="hidden"><?php echo _("Selected Address books:") ?></label>
    <select name="selected_search_sources" multiple="multiple" size="5" style="width:20em" id="selected_search_sources">
     <option value=""><?php echo _("Selected Address books:") ?></option>
<?php foreach ($cache['prefSelect'] as $key => $val): ?>
     <option value="<?php echo $key ?>"><?php echo $val ?></option>
<?php endforeach; ?>
    </select>
   </td>
   <td>
    <a href="#" id="moveup"><?php echo Horde::img('nav/up.png', _("Move up"), null, $imagedir) ?></a>
    <br />
    <a href="#" id="movedown"><?php echo Horde::img('nav/down.png', _("Move down"), null, $imagedir) ?></a>
   </td>
  </tr>
 </table>

 <p><?php echo _("Click on one of your selected address books and then select all fields to search.") ?></p>
<?php else: ?>
 <p><?php echo _("Select all fields to search when expanding addresses.") ?></p>
<?php endif; ?>

 <div><?php echo _("To select multiple fields, hold down the Control (PC) or Command (Mac) while clicking.") ?></div>
 <input type="hidden" name="search_fields_string" id="search_fields_string" />
 <table>
  <tr>
   <td>
    <label for="search_fields" class="hidden"><?php echo _("Fields to search") ?></label>
    <select id="search_fields" name="search_fields" multiple="multiple" size="5" style="width:20em">
     <option><?php echo str_repeat('&nbsp;', 50) ?></option>
    </select>
   </td>
  </tr>
 </table>
<?php endif; ?>

<?php if (!empty($cache['writeSelect'])): ?>
 <div><?php echo _("Choose the address book to use when adding addresses.") ?></div>
 <label for="add_source" class="hidden"><?php echo _("Address book to add addresses to") ?></label>
 <select id="add_source" name="add_source">
  <option value=""><?php echo _("None") ?></option>
<?php foreach ($cache['writeSelect'] as $val): ?>
  <option value="<?php echo $val['val'] ?>"<?php if ($val['sel']) echo ' selected="selected"'; ?>><?php echo $val['name'] ?></option>
<?php endforeach; ?>
 </select>
<?php endif; ?>
</div>
