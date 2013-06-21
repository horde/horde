<p class="horde-content">
 <?php echo Chora::url('browsedir', $where . '/', $branchArgs + array('sa' => ($acts['sa'] ? 0 : 1)))->link() . ($acts['sa'] ? Horde::img('checkbox_on.png') : Horde::img('checkbox_off.png')) . ' ' . _("Show Deleted Files") . '</a>' ?>
</p>
