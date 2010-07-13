<script type="text/javascript">
var loading;

function selectAll()
{
    for (var i = 0; i < document.gallery.elements.length; ++i) {
        document.gallery.elements[i].checked = true;
    }
}

function selectNone()
{
    for (var i = 0; i < document.gallery.elements.length; ++i) {
        document.gallery.elements[i].checked = false;
    }
}

function deleteSelected()
{
    if (loading == null && confirm('<?php echo addslashes(_("Are you sure you want to delete the selected photos?")) ?>')) {
        loading = true;
        document.gallery.actionID.value = 'delete';
        document.gallery.submit();
    }
}

function moveSelected()
{
    if (document.gallery.new_gallery.selectedIndex == 0) {
        window.alert('<?php echo addslashes(_("You must choose a gallery to move photos to.")) ?>');
        return false;
    }

    if (loading == null) {
        loading = true;
        document.gallery.actionID.value = 'move';
        document.gallery.submit();
    }
}

function copySelected()
{
    if (document.gallery.new_gallery.selectedIndex == 0) {
        window.alert('<?php echo addslashes(_("You must choose a gallery to move photos to.")) ?>');
        return false;
    }

    if (loading == null) {
        loading = true;
        document.gallery.actionID.value = 'copy';
        document.gallery.submit();
    }
}
</script>
<?php if ($numimages): ?>
<form name="gallery" action="<?php Horde::applicationUrl('view.php', true)?>" method="get">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="" />
<input type="hidden" name="view" value="Results" />
<?php endif; ?>

<div class="header">
<?php
/* Build link, if needed, for browsing all users' results */
$allLink = (!empty($this->_owner)) ? Horde::selfUrl()->add(array('view' => 'Results'))->link(array('title' => _("View Results from All Users"))) . ' (' . _("View All Results") . ')</a>' : '';
if ($total) {
    echo '<span class="rightFloat">' . ($total > $this->_perPage ? sprintf(_("%d to %d of %d Items"), $this->_pagestart, $this->_pageend, $total) : sprintf(ngettext("%d Item", "%d Items", $total), $total)) . '<small>' . $allLink . '</small></span>';
}
echo htmlspecialchars($this->getTitle(), ENT_COMPAT, $GLOBALS['registry']->getCharset()) . $this->_search->getTagTrail();
?>
</div>

<table class="control" width="100%" cellspacing="0">
 <tr>
  <td>
    <?php printf(_("Select: %s, %s"),
                   Horde::link('#', _("Select All"), '', '', 'selectAll(); return false;') . _("All") . '</a>',
                   Horde::link('#', _("Select None"), '', '', 'selectNone(); return false;') . _("None") . '</a>');?>
  </td>
  <td class="rightAlign">
<?php echo _("Actions: ") ?>
    <?php echo Horde::link('#', _("Delete"), 'widget', '', 'deleteSelected(); return false;') . _("Delete"); ?></a>
<?php if ($option_move): ?>
 |  <?php echo Horde::link('#', _("Move"), 'widget', '', 'moveSelected(); return false;') . _("Move"); ?></a>
<?php endif; ?>
<?php if ($option_copy): ?>
 |  <?php echo Horde::link('#', _("Copy"), 'widget', '', 'copySelected(); return false;') . _("Copy"); ?></a>
<?php endif; ?>
    <select name="new_gallery"><option value="-1"><?php echo _("Selected photos to") ?></option><?php echo Ansel::selectGalleries(array('perms' => Horde_Perms::EDIT))?></select>
  </td>
 </tr>
</table>

<table width="100%">
 <tbody>
  <tr>
   <td valign="top">
    <?php if (!$total): ?>
     <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
    <?php else: ?>
     <?php echo $this->_pager->render() ?>
     <table width="100%" style="background-color:<?php echo $styleDef['background'] ?>;"><tr>
      <?php
        $cellwidth = round(100 / $tilesperrow);
        $count = 0;
        foreach ($results as $result) {
            echo '<td width="' . $cellwidth . '%" valign="top">' . $result->getTile(null, $style, false, $this->_params) . '</td>';
            if (!(++$count % $tilesperrow)) {
                if ($count < $total) {
                    echo '</tr><tr>';
                }
            }
        }

        while ($count++ % $tilesperrow) {
            echo '<td>&nbsp;</td>';
        } ?>
      </tr>
     </table>
     <?php echo $this->_pager->render() ?>
    <?php endif; ?>
   </td>
   <td width="20%" valign="top">
    <div id="anselWidgets">
     <?php /* Tags if we are using related */ if ($conf['tags']['relatedtags']): ?>
      <div style="background-color:<?php echo $styleDef['background'] ?>;">
       <h2 class="header tagTitle"><?php echo _("Related Tags") ?></h2>
       <div id="tags"><?php echo $rtaghtml ?></div>
      </div>
     <?php endif; ?>
    </div>
   </td>
  </tr>
 </tbody>
</table>

<?php if ($numimages): ?>
</form>
<?php endif; ?>
