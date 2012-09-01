<?php

class Turba_Hooks
{
   public function prefs_init($pref, $value, $username, $scope_ob)
   {
       switch ($pref) {
       case 'default_dir':
           $addressbooks = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           $primary = null;
           $primary_share = null;
           foreach ($addressbooks as $id => $addressbook) {
               $default = $addressbook->get('default');
               if (!empty($default)) {
                   if (!empty($primary_share)) {
                       $GLOBALS['notification']->push(
                           sprintf(
                               "Both shares '%s' and '%s' are marked as default addressbook! Please notify your administrator.",
                               $primary_share->get('name'),
                               $addressbook->get('name')
                           ),
                           'horde.error'
                       );
                   }
                   $primary = $id;
                   $primary_share = $addressbook;
               }
           }
           return $primary;
       }
   }

   public function prefs_change($pref)
   {
       switch ($pref) {
       case 'default_dir':
           $value = $GLOBALS['prefs']->getValue('default_dir');
           $addressbooks = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           foreach ($addressbooks as $id => $addressbook) {
               if ($id == $value) {
                   $addressbook->set('default', true);
                   $addressbook->save();
                   break;
               }
           }
           break;
       }
   }
}