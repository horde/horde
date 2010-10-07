<?php if (empty($this->view->api)): ?>
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
    var haveImages = false;
    for (var i = 0; i< document.gallery.elements.length; ++i) {
        if (document.gallery.elements[i].checked) {
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

function editDates()
{
    var haveImages = false;
    var imageDateUrl = '<?php echo Horde::url('edit_dates.php')->add(array('gallery' => $this->galleryId)) ?>';
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
<script type="text/javascript">
    //<![CDATA[
    LightboxOptions = {
        gallery_json: <?php echo $json; ?>,
        fileLoadingImage:        '<?php echo $jsvars['graphics_dir'];?>/lightbox/loading.gif',
        fileBottomNavCloseImage: '<?php echo $jsvars['graphics_dir'];?>/lightbox/closelabel.gif',

        overlayOpacity: 0.8,   // controls transparency of shadow overlay
        animate: true,         // toggles resizing animations
        resizeSpeed: 7,        // controls the speed of the image resizing animations (1=slowest and 10=fastest)
        borderSize: 10,         //if you adjust the padding in the CSS, you will need to update this variable

        // Used to write: Image # of #.
        labelImage: "<?php echo $jsvars['image_text'];?>",
        labelOf: "<?php echo $jsvars['of_text'];?>",
        //URL to return to when the lightbox closes
        returnURL: "<?php echo $jsvars['gallery_url']; ?>",
        startPage: "<?php echo $jsvars['start_page']; ?>"
    };
    //]]>
    <?php if($GLOBALS['browser']->getBrowser() != 'msie'):?>
      document.observe('dom:loaded', function () { document.lb = new Lightbox(LightboxOptions); if (window.location.hash.length) document.lb.start(window.location.hash.substring(1));});
    <?php else:?>
      Event.observe(window, 'load', function () { document.lb = new Lightbox(LightboxOptions); if (window.location.hash.length) document.lb.start(window.location.hash.substring(1));});
    <?php endif; ?>
</script>
<div class="header" id="galleryHeader">
 <?php echo ($this->numTiles ? '<span class="rightFloat">' . ($this->numTiles > $this->perpage ? sprintf(_("%d to %d of %d Items"), $this->pagestart, $this->pageend, $this->numTiles) : sprintf(ngettext("%d Item", "%d Items", $this->numTiles), $this->numTiles)) . '</span>' : '') . Ansel::getBreadCrumbs($this->view->gallery) ?>
</div>
<?php if ($this->view->gallery->get('desc')): ?>
<div class="gallery-desc" id="galleryDescription">
<?php echo $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($this->view->gallery->get('desc'), 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)) ?>
</div>
<?php endif;?>
<table width="100%" cellspacing="0">
 <tr>
  <td style="vertical-align:top;width:<?php echo ($this->view->countWidgets() ? "75%" : "100%") ?>;">
    <?php if (empty($this->view->api) && $option_select && $this->numTiles): ?>
    <form name="gallery" action="<?php echo Horde::url('image.php', true)?>" method="get">
    <?php echo Horde_Util::formInput() ?>
    <input type="hidden" name="actionID" value="" />
    <input type="hidden" name="gallery" value="<?php echo $this->galleryId ?>" />
    <input type="hidden" name="page" value="<?php echo htmlspecialchars($this->page) ?>" />
    <input type="hidden" name="year" value="<?php echo (empty($this->view->year) ? 0 : $this->view->year) ?>" />
    <input type="hidden" name="month" value="<?php echo (empty($this->view->month) ? 0 : $this->view->month) ?>" />
    <input type="hidden" name="day" value="<?php echo (empty($this->view->day) ? 0 : $this->view->day) ?>" />
    <?php endif; ?>
    <?php if (!empty($option_select) && $this->numTiles): ?>
        <table class="anselActions" cellspacing="0" width="100%"><tr><td>
        <?php   $tempurl = new Horde_Url('#');
                printf(_("Select: %s, %s"),
                       $tempurl->link(array('title' => _("Select All"), 'onclick' => 'selectAll(); return false;')) . _("All") . '</a>',
                       $tempurl->link(array('title' => _("Select None"), 'onclick' => 'selectNone(); return false;')) . _("None") . '</a>');?>
       </td><td align="right">
        <?php if ($option_delete || $option_move || $option_copy) echo _("Actions: ") ?>
          <?php if ($GLOBALS['conf']['gallery']['downloadzip']): ?>
            <?php echo $tempurl->link(array('class' => 'widget', 'onclick' => 'downloadSelected(); return false;')) . _("Download selected photos")?></a> |
          <?php endif; ?>
          <?php if ($option_edit): ?>
           <?php echo $tempurl->link(array('title' => _("Edit Dates"), 'class' => 'widget', 'onclick' => 'editDates(); return false;')) . _("Edit Dates"); ?></a> |
          <?php endif; ?>
          <?php if ($option_delete): ?>
            <?php echo $tempurl->link(array('title' => _("Delete"), 'class' => 'widget', 'onclick' => 'deleteSelected(); return false;')) . _("Delete"); ?></a>
          <?php endif; ?>
          <?php if ($option_move): ?>
          | <?php echo $tempurl->link(array('title' => _("Move"), 'class' => 'widget', 'onclick' => 'moveSelected(); return false;')) . _("Move"); ?></a>
          <?php endif; ?>
          <?php if ($option_copy): ?>
     |      <?php echo $tempurl->link(array('title' => _("Copy"), 'class' => 'widget', 'onclick' => 'copySelected(); return false;')) . _("Copy"); ?></a>
          <?php endif; ?>
          <select name="new_gallery"><option value="-1"><?php echo _("Selected photos to") ?></option><?php echo Ansel::selectGalleries(array('selected' => $this->galleryId, 'perm' => Horde_Perms::EDIT)) ?></select>
          </td></tr></table>
        <?php endif; ?>
     <?php if (!$this->numTiles): ?>
      <div class="text"><em><?php echo _("There are no photos in this gallery.") ?></em></div>
     <?php else: ?>
      <table width="100%" style="background-color:<?php echo $this->style->background ?>;">
       <tr><td colspan="<?php echo $tilesperrow ?>"><?php echo $pager->render() ?></td></tr>
       <tr>
       <?php
       foreach ($this->children as $child) {
           echo '<td width="' . $cellwidth . '%" class="ansel-tile">'
               . $child->getTile($this->view->gallery, $this->style, false, $this->view->getParams()) . '</td>';
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
<?php if ($this->view->countWidgets()): ?>
  </div>
<?php endif; ?>
