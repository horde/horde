<?php

class Mnemo_Hooks
{
   public function prefs_init($pref, $value, $username, $scope_ob)
   {
       switch ($pref) {
       case 'default_notepad':
           $notepads = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           $primary = null;
           $primary_share = null;
           foreach ($notepads as $id => $notepad) {
               $default = $notepad->get('default');
               if (!empty($default)) {
                   if (!empty($primary_share)) {
                       $GLOBALS['notification']->push(
                           sprintf(
                               "Both shares '%s' and '%s' are marked as default notepad! Please notify your administrator.",
                               $primary_share->get('name'),
                               $notepad->get('name')
                           ),
                           'horde.error'
                       );
                   }
                   $primary = $id;
                   $primary_share = $notepad;
               }
           }
           return $primary;
       }
   }
}