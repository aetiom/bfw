<?php

namespace BFW\Install\Test\Helpers;

//To be included by module who use it
require_once(__DIR__.'/../../mocks/src/class/install/Application.php');

trait Application
{
    /**
     * @var \BFW\Install\Test\Mock\Application $app
     */
    protected $app;
    
    /**
     * @var string $rootDir : The root directory path of the application
     */
    protected $rootDir;
    
    /**
     * Setter accessor for rootDir property
     * 
     * @param string $rootDir
     * 
     * @return $this
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
        return $this;
    }
    
    /**
     * Create the bfw Application instance used by the install system
     * 
     * @return void
     */
    protected function createApp()
    {
        $mockedConfigValues = require(
            realpath(__DIR__.'/../../../../skel/app/config/bfw/config.php')
        );
        
        $this->app = \BFW\Install\Test\Mock\Application::getInstance();
        $this->app->setMockedConfigValues($mockedConfigValues);
    }
    
    /**
     * Call the method initSystem of the bfw Application class
     * 
     * @param boolean $runSession (default false)
     * 
     * @return void
     */
    protected function initApp($runSession = false)
    {
        $this->app->initSystem([
            'rootDir'    => realpath($this->rootDir),
            'vendorDir'  => realpath($this->rootDir.'/vendor'),
            'runSession' => $runSession
        ]);
    }
}
