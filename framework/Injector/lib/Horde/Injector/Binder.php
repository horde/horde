<?php
interface Horde_Injector_Binder
{
    public function create(Horde_Injector $injector);

    /**
     * determine if one binder equals another binder
     *
     * @param Horde_Injector_Binder $binder The binder to compare against $this
     * @return bool true if they are equal, or false if they are not equal
     */
    public function equals(Horde_Injector_Binder $binder);
}
