<?php
/**
 * Interface for the object that builds a request chain around a controller.
 *
 * @category Horde
 * @package  Controller
 * @author   Bob McKee <bob@bluestatedigital.com>
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
interface Horde_Controller_SettingsExporter
{
    /**
     */
    public function exportBindings(Horde_Injector $injector);

    /**
     */
    public function exportFilters(Horde_Controller_FilterCollection $filters, Horde_Injector $injector);
}
