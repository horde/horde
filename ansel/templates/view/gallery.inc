<?php if (empty($this->view->api)): ?>
<script type="text/javascript">
var loading;

function selectAll()
{
    for (var i = 0; i < document.forms['gallery'].elements.length; ++i) {
        document.forms['gallery'].elements[i].checked = true;
    }
}

function selectNone()
{
    for (var i = 0; i < document.forms['gallery'].elements.length; ++i) {
        document.forms['gallery'].elements[i].checked = false;
    }
}

function deleteSelected()
{
    var haveImages = false;
    for (var i = 0; i< document.forms['gallery'].elements.length; ++i) {
        if (document.forms['gallery'].elements[i].checked) {
            haveImages = true;
            break;
        }
    }
    if (!haveImages) {
        alert('<?php echo addslashes(_("You must choose photos to delete.")) ?>');
        return false;
    }
    if (loading == null && confirm('<?php echo addslashes(_("Are you sure you want to delete the selected photos?")) ?>')) {
        loading = true;
        document.forms['gallery'].actionID.value = 'delete';
        document.forms['gallery'].submit();
        loading = false;
    }
}

function moveSelected()
{
    if (document.forms['gallery'].new_gallery.selectedIndex == 0) {
        window.alert('<?php echo addslashes(_("You must choose a gallery to move photos to.")) ?>');
        return false;
    }

    if (!loading) {
        loading = true;
        document.forms['gallery'].actionID.value = 'move';
        document.forms['gallery'].submit();
        loading = false;
    }
}

function copySelected()
{
    if (document.forms['gallery'].new_gallery.selectedIndex == 0) {
        window.alert('<?php echo addslashes(_("You must choose a gallery to move photos to.")) ?>');
        return false;
    }

    if (!loading) {
        loading = true;
        document.forms['gallery'].actionID.value = 'copy';
        document.forms['gallery'].submit();
        loading = false;
    }
}

function editDates()
{
    var haveImages = false;
    var imageDateUrl = '<?php echo Horde::applicationUrl('edit_dates.php')->add(array('gallery' => $this->galleryId)) ?>';
    for (var i = 0; i< document.forms['gallery'].elements.length; ++i) {
       if (document.forms['gallery'].elements[i].checked == true &&
           document.forms['gallery'].elements[i].type == 'checkbox') {
            haveImages = true;
            imageDateUrl = imageDateUrl + '&' + document.forms['gallery'].elements[i].name + '=true';
        }
    }
    if (!haveImages) {
        alert('<?php echo addslashes(_("You must choose photos to edit.")) ?>');
        return false;
    }
    Horde.popup({ url: imageDateUrl, width: 600, height: 250 });
}

function downloadSelected()
{
    if (!loading) {
        loading = true;
        document.forms['gallery'].actionID.value = 'downloadzip';
        document.forms['gallery'].submit();
        loading = false;
    }

}


</script>
<?php endif; ?>
<div id="galleryHeader" class="header">
  <?php if ($this->numTiles): ?>
  <span class="rightFloat">
  <?php echo $this->numTiles > $this->perpage ? sprintf(_("%d to %d of %d items"), $this->pagestart, $this->pageend, $this->numTiles) : sprintf(ngettext("%d item", "%d items", $this->numTiles), $this->numTiles) ?>
  </span>
  <?php endif; ?>
  <?php echo Ansel::getBreadCrumbs($this->view->gallery) ?>
</div>
<?php if ($this->view->gallery->get('desc')): ?>
    <div id="galleryDescription" class="gallery-desc">
    <?php echo $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($this->view->gallery->get('desc'), 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
    </div>
<?php endif; ?>
<table cellspacing="0" width="100%">
 <tr>
  <td style="vertical-align:top;width:<?php echo ($this->view->countWidgets() ? "75%" : "100%") ?>;">
    <?php if (empty($this->view->api) && $option_select && $this->numTiles): ?>
    <form name="gallery" action="<?php echo Horde::applicationUrl('image.php', true)?>" method="get">
    <?php echo Horde_Util::formInput() ?>
    <input type="hidden" name="actionID" value="" />
    <input type="hidden" name="gallery" value="<?php echo htmlspecialchars($this->galleryId) ?>" />
    <input type="hidden" name="page" value="<?php echo htmlspecialchars($this->page) ?>" />
    <input type="hidden" name="year" value="<?php echo (empty($this->view->year) ? 0 : $this->view->year) ?>" />
    <input type="hidden" name="month" value="<?php echo (empty($this->view->month) ? 0 : $this->view->month) ?>" />
    <input type="hidden" name="day" value="<?php echo (empty($this->view->day) ? 0 : $this->view->day) ?>" />

    <?php endif; ?>
    <?php if (!empty($option_select) && $this->numTiles): ?>
    <table class="anselActions" cellspacing="0" width="100%"><tr><td>
    <?php   $tempurl = new Horde_Url('#');
            echo '<span class="widget">' . _("Select:") . '</span> '
              . $tempurl->link(array('class' => 'widget', 'onclick' => 'selectAll(); return false;'))
              . _("All") . '</a> | '
              . $tempurl->link(array('class' => 'widget', 'onclick' => 'selectNone(); return false;'))
              . _("None") . '</a>'; ?>
    </td>
    <td align="right">
      <?php echo implode(' | ', $action_links); ?>
      <select name="new_gallery"><option value="-1"><?php echo _("Selected photos to") ?></option><?php echo Ansel::selectGalleries(array('perms' => Horde_Perms::EDIT))?></select>
      </td></tr></table>
    <?php endif; ?>
    <?php if (!$this->numTiles): ?>
      <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
    <?php else: ?>
      <table width="100%" style="background-color:<?php echo $this->style['background'] ?>;">
        <tr><td colspan="<?php echo $tilesperrow ?>"><?php echo $pager->render() ?></td></tr>
        <tr>
          <?php
          foreach ($this->children as $child) {
              echo '<td width="' . $cellwidth . '%" class="ansel-tile">'
                  . $child->getTile($this->view->gallery, $this->style['name'], false, $this->view->getParams()) . '</td>';
              if (!(++$count % $tilesperrow)) {
                echo '</tr><tr>';
              }
          }
          while ($count % $tilesperrow) {
              echo '<td width="' . $cellwidth . '%" valign="top">&nbsp;</td>';
              $count++;
          }?>
        </tr>
        <tr><td colspan="<?php echo $tilesperrow ?>"><?php echo $pager->render() ?></td></tr>
      </table>
    <?php endif; ?>
    <?php if (!empty($option_select) && $this->numTiles): ?>
      </form>
    <?php endif; ?>
   </td>
   <td class="anselWidgets">
    <?php echo $this->view->renderWidgets() ?>
   </td>
  </tr>
 </table>
