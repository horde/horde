<?php
/**
 * index view for rendering channel list. Expects:
 *  ->search_img
 *  ->channels
 *
 *
 */
?>
<div class="header">
 <?php echo _("Manage Feeds") ?>
 <a id="quicksearchL" href="#" title="<?php echo _("Search")?>" onclick="$('quicksearchL').hide(); $('quicksearch').show(); $('quicksearchT').focus(); return false;"><?php echo $this->search_img?></a>
 <div id="quicksearch" style="display:none;">
  <input type="text" name="quicksearchT" id="quicksearchT" for="feeds-body" empty="feeds-empty" />
  <small>
   <a title="<?php echo _("Close Search")?>" href="#" onclick="$('quicksearch').hide(); $('quicksearchT').value = ''; QuickFinder.filter($('quicksearchT')); $('quicksearchL').show(); return false;">X</a>
  </small>
 </div>
</div>

<?php if (count($this->channels)):?>
    <table id="feeds" width="100%" class="sortable" cellspacing="0">
    <thead>
     <tr>
      <th width="1%">&nbsp;</th>
      <th class="sortdown"><?php echo _("Name")?></th>
      <th><?php echo _("Type")?></th>
      <th><?php echo _("Last Update")?></th>
     </tr>
    </thead>

    <tbody id="feeds-body">
     <?php foreach ($this->channels as $channel):?>
     <tr>
      <td class="nowrap">
       <?php echo $channel['edit_link'] . $channel['refresh_link'] . $channel['addstory_link'] . $channel['delete_link'];?>
      </td>
      <td>
       <a href="<?php echo $channel['stories_url']?>"><?php echo $channel['channel_name']?></a>
      </td>
      <td><?php echo $channel['channel_type']?></td>
      <td class="linedRow"><?php echo $channel['channel_updated']?></td>
     </tr>
     <?php endforeach?>
    </tbody>
    </table>
    <div id="feeds-empty">
     <?php echo _("No feeds match")?>
    </div>
<?php else:?>
    <div class="text">
     <em><?php echo _("No channels are available.")?></em>
    </div>
<?php endif;?>