<?php

class Horde_Hooks
{
   public function prefs_init($pref, $value, $username, $scope_ob)
   {
       switch ($pref) {
       case 'from_addr':
           // The hook example for the Kolab server default setup.

           // Example #3
           if (is_null($username)) {
               return $value;
           }

           return $GLOBALS['injector']->getInstance('Horde_Kolab_Session')
               ->getMail();

       case 'fullname':
           // Examples on how to set the fullname.

           // Example #3
           if (is_null($username)) {
               return $value;
           }

           return $GLOBALS['injector']->getInstance('Horde_Kolab_Session')
               ->getName();
       }
   }
}