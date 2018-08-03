<?php

namespace BFW\Core\AppSystems;

interface SystemInterface
{
    /**
     * To init the core system
     * 
     * @return void
     */
    public function init();
    
    /**
     * Return the initStatus value.
     * To know if the init method has already been called.
     * 
     * @return boolean
     */
    public function isInit();
    
    /**
     * To know if the run method should be called
     * 
     * @return boolean
     */
    public function toRun();
    
    /**
     * Return the runStatus value.
     * To know if the run method has already been called.
     * 
     * @return boolean
     */
    public function isRun();
    
    /**
     * To run the core system
     * 
     * @return void
     */
    public function run();
}
