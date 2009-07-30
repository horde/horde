<script type="text/javascript">
    //<![CDATA[
     var clips = [];
    <?php foreach(array_reverse($this->_thumbs) as $video):?>
        clips.push(<?php echo $this->_json[$video['clip_id']]?>);
    <?php endforeach ?>

    function showVideo(index)
    {
         RedBox.showHtml('<div id="RB_info">' + clips[index]['html'] + '</div>');
         $('RB_window').setStyle({top: '100px'});
         $('RB_overlay').observe('click', function() {RedBox.close()});
         return false;
    }
    //]]>

</script>
<div class="header" id="galleryHeader">
 <?php echo ($this->numTiles ? '<span class="rightFloat">' . ($this->numTiles > $this->perpage ? sprintf(_("%d to %d of %d Items"), $this->pagestart, $this->pageend, $this->numTiles) : sprintf(ngettext("%d Item", "%d Items", $this->numTiles), $this->numTiles)) . '</span>' : '') . Ansel::getBreadCrumbs(); ?>
</div>
<?php if ($this->view->gallery->get('desc')): ?>
<div class="gallery-desc" id="galleryDescription">
<?php echo Text_Filter::filter($this->view->gallery->get('desc'), 'text2html', array('parselevel' => TEXT_HTML_MICRO)) ?>
</div>
<?php endif;?>
<table width="100%" cellspacing="0">
 <tr>
  <td style="vertical-align:top;width:<?php echo ($this->view->countWidgets() ? "75%" : "100%") ?>;">
     <?php if (!$this->numTiles): ?>
      <div class="text"><em><?php echo _("There are no videos in this gallery.") ?></em></div>
     <?php else: ?>
      <table width="100%" style="background-color:<?php echo $this->style['background'] ?>;">
       <tr><td colspan="<?php echo $tilesperrow ?>"><?php echo $pager->render() ?></td></tr>
       <tr>
       <?php
       $cnt = 0;
       foreach ($this->children as $child) {
           echo '<td width="' . $cellwidth . '%" class="ansel-tile">'
               . $this->getTile($child, new Horde_Support_Array($this->_thumbs[$child->caption]), $cnt++) . '</td>';
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
     <?php endif;?>
   </td>
   <td class="anselWidgets">
      <?php if (empty($this->view->_params['api'])):?>
     <?php echo $this->view->renderWidgets(); ?>
     <?php endif;?>
   </td>
 </tr>
</table>
<?php if ($this->view->countWidgets()): ?>
  </div>
<?php endif; ?>
