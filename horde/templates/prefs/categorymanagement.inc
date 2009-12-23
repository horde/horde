<?php
Horde::addScriptFile('prototype.js', 'horde');
Horde::addScriptFile('colorpicker.js', 'horde');
$cManager = new Horde_Prefs_CategoryManager();
$categories = $cManager->get();
$colors = $cManager->colors();
$fgcolors = $cManager->fgColors();

// Default Color
$color = isset($colors['_default_']) ? $colors['_default_'] : '#FFFFFF';
$fgcolor = isset($fgcolors['_default_']) ? $fgcolors['_default_'] : '#000000';
$color_b = 'color_' . hash('md5', '_default_');
?>
<script type="text/javascript">
function removeCategory(category)
{
    var p = $('prefs');
    $(p.cAction).setValue('remove');
    $(p.category).setValue(category);
    p.submit();
}

function addCategory()
{
    var category = window.prompt('<?php echo addslashes(_("Please enter a name for the new category:")) ?>', ''), p;
    if (category != null && category != '') {
        p = $('prefs');
        $(p.cAction).setValue('add');
        $(p.category).setValue(category);
        p.submit();
    }
}

function resetBackgrounds()
{
    $('prefs').getInputs('text').each(function(i) {
        if (i.readAttribute('id').startsWith('color_')) {
            i.setStyle({ backgroundColor: $F(i) });
        }
    });
}

document.observe('dom:loaded', function() {
    $('prefs').observe('reset', function() { resetBackgrounds.defer() });
});
</script>
<input name="cAction" value="" type="hidden" />
<input name="category" value="" type="hidden" />
<input type="button" class="button" value="<?php echo _("New Category") ?>" onclick="addCategory();" />
<table>
 <tr>
  <td style="background-color:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>"><strong><?php echo Horde::label($color_b, _("Default Color")) ?></strong></td>
  <td>
   <?php if (!$prefs->isLocked('category_colors')): ?>
    <input size="7" style="background:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>" id="<?php echo $color_b ?>" name="<?php echo $color_b ?>" value="<?php echo htmlspecialchars($color) ?>" />
    <a href="#" onclick="new ColorPicker({ color: $F('<?php echo $color_b ?>'), offsetParent: Event.element(event), update: [['<?php echo $color_b ?>', 'value'], ['<?php echo $color_b ?>', 'background']] }); return false;">
    <?php echo Horde::img('colorpicker.png', _("Color Picker"), 'height="16"', $registry->getImageDir('horde')) ?></a>
   <?php endif; ?>
  </td>
 </tr>
<?php
// Unfiled Color
$color = isset($colors['_unfiled_']) ? $colors['_unfiled_'] : '#FFFFFF';
$fgcolor = isset($fgcolors['_unfiled_']) ? $fgcolors['_unfiled_'] : '#000000';
$color_b = 'color_' . hash('md5', '_unfiled_');
?>
 <tr>
  <td style="background-color:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>"><strong><?php echo Horde::label($color_b, _("Unfiled")) ?></strong></td>
  <td>
   <?php if (!$prefs->isLocked('category_colors')): ?>
    <input size="7" style="background:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>" id="<?php echo $color_b ?>" name="<?php echo $color_b ?>" value="<?php echo htmlspecialchars($color) ?>" />
    <a href="#" onclick="new ColorPicker({ color: $F('<?php echo $color_b ?>'), offsetParent: Event.element(event), update: [['<?php echo $color_b ?>', 'value'], ['<?php echo $color_b ?>', 'background']] }); return false;">
    <?php echo Horde::img('colorpicker.png', _("Color Picker"), 'height="16"', $registry->getImageDir('horde')) ?></a>
   <?php endif; ?>
  </td>
 </tr>
<?php
foreach ($categories as $name):
    $color = isset($colors[$name]) ? $colors[$name] : '#FFFFFF';
    $fgcolor = isset($fgcolors[$name]) ? $fgcolors[$name] : '#000000';
    $color_b = 'color_' . hash('md5', $name);
?>
 <tr>
  <td style="background-color:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>"><strong><?php echo Horde::label($color_b, ($name == '_default_' ? _("Default Color") : htmlspecialchars($name))) ?></strong></td>
  <td>
   <?php if (!$prefs->isLocked('category_colors')): ?>
    <input size="7" style="background:<?php echo htmlspecialchars($color) ?>; color:<?php echo $fgcolor ?>" id="<?php echo $color_b ?>" name="<?php echo $color_b ?>" value="<?php echo htmlspecialchars($color) ?>" />
    <a href="#" onclick="new ColorPicker({ color: $F('<?php echo $color_b ?>'), offsetParent: Event.element(event), update: [['<?php echo $color_b ?>', 'value'], ['<?php echo $color_b ?>', 'background']] }); return false;">
    <?php echo Horde::img('colorpicker.png', _("Color Picker"), 'height="16"', $registry->getImageDir('horde')) ?></a>
    <?php endif; ?>
    <a href="#" onclick="removeCategory(decodeURIComponent('<?php echo rawurlencode(Horde_String::convertCharset($name, Horde_Nls::getCharset(), 'UTF-8')) ?>')); return false;"><?php echo Horde::img('delete.png') ?></a>
  </td>
 </tr>
<?php endforeach; ?>
</table>
