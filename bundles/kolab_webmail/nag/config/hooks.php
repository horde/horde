<?php

class Nag_Hooks
{
   public function prefs_init($pref, $value, $username, $scope_ob)
   {
       switch ($pref) {
       case 'default_tasklist':
           $tasklists = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           $primary = null;
           $primary_share = null;
           foreach ($tasklists as $id => $tasklist) {
               $default = $tasklist->get('default');
               if (!empty($default)) {
                   if (!empty($primary_share)) {
                       $GLOBALS['notification']->push(
                           sprintf(
                               "Both shares '%s' and '%s' are marked as default tasklist! Please notify your administrator.",
                               $primary_share->get('name'),
                               $tasklist->get('name')
                           ),
                           'horde.error'
                       );
                   }
                   $primary = $id;
                   $primary_share = $tasklist;
               }
           }
           return $primary;
       }
   }

   public function prefs_change($pref)
   {
       switch ($pref) {
       case 'default_tasklist':
           $value = $GLOBALS['prefs']->getValue('default_tasklist');
           $tasklists = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           foreach ($tasklists as $id => $tasklist) {
               if ($id == $value) {
                   $tasklist->set('default', true);
                   $tasklist->save();
                   break;
               }
           }
           break;
       }
   }
}