<!-- Begin Deliverable Detail Dialog -->
<div id="hermesDeliverableDetail" class="hermesDialog" style="display:none;">
 <h1><?php echo _("Deliverable Detail") . ':' ?> <span></span></h1>
 <div class="hermesBox">
   <div class="hermesBudget">
     <h3><?php echo _("Budget") ?></h3>
     <div id="hermesStatText">
       <table><tbody>
         <tr><th></th><th></th><th></th></tr>
         <tr><td><?php echo _("Logged")?></td><td><?php echo _("Budget") ?></td><td><?php echo _("Remaining") ?></td></tr>
      </tbody></table>
     </div>
     <div id="hermesDeliverableStats"></div>
   </div>
 </div>
 <div class="hermesBox"><div class="hermesDeliverablePies">
   <div id="hermesDeliverableBillable">&nbsp;</div>
   <div id="hermesDeliverableType">&nbsp;</div>
  </div></div>
 <div class="hermesBox"><div class="hermesDeliverableParticipants">
   <h3><?php echo _("Participants") ?></h3>
   <div id="hermesDeliverableEmployees">&nbsp;</div>
 </div></div>
 <div class="clear"></div>
 <div class="hermesFormActions">
   <input id="hermesDeliverablesClose" type="button" class="horde-default" value="<?php echo _("Close") ?>" />
 </div>
</div>
<!-- End Deliverable Detail Dialog -->