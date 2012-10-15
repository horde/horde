<?php

class Kronolith_Hooks
{
   public function prefs_init($pref, $value, $username, $scope_ob)
   {
       switch ($pref) {
       case 'default_share':
           $calendars = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           $primary = null;
           $primary_share = null;
           foreach ($calendars as $id => $calendar) {
               $default = $calendar->get('default');
               if (!empty($default)) {
                   if (!empty($primary_share)) {
                       $GLOBALS['notification']->push(
                           sprintf(
                               "Both shares '%s' and '%s' are marked as default calendar! Please notify your administrator.",
                               $primary_share->get('name'),
                               $calendar->get('name')
                           ),
                           'horde.error'
                       );
                   }
                   $primary = $id;
                   $primary_share = $calendar;
               }
           }
           return $primary;
       }
   }

   public function prefs_change($pref)
   {
       switch ($pref) {
       case 'default_share':
           $value = $GLOBALS['prefs']->getValue('default_share');
           $calendars = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')
               ->create()
               ->listShares(
                   $GLOBALS['registry']->getAuth(),
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $GLOBALS['registry']->getAuth()));
           foreach ($calendars as $id => $calendar) {
               if ($id == $value) {
                   $calendar->set('default', true);
                   $calendar->save();
                   break;
               }
           }
           break;
       }
   }
}