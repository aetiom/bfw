<?php

namespace BFW\Core\AppSystems\test\unit;

use \atoum;

require_once(__DIR__.'/../../../../../vendor/autoload.php');

/**
 * @engine isolate
 */
class AbstractSystem extends atoum
{
    protected $mock;
    
    public function beforeTestMethod($testMethod)
    {
        $this->mock = new \mock\BFW\Core\AppSystems\AbstractSystem;
    }
    
    public function testInitAndIsInit()
    {
        $this->assert('test Core\AppSystems\AbstractSystem::isInit before init')
            ->boolean($this->mock->isInit())
                ->isFalse()
        ;
        
        $this->assert('test Core\AppSystems\AbstractSystem::init and isInit after')
            ->variable($this->mock->init())
                ->isNull()
            ->boolean($this->mock->isInit())
                ->isTrue()
        ;
    }
    
    public function testToRun()
    {
        $this->assert('test Core\AppSystems\AbstractSystem::toRun')
            ->boolean($this->mock->toRun())
                ->isFalse()
        ;
    }
    
    public function testRunAndIsRun()
    {
        $this->assert('test Core\AppSystems\AbstractSystem::isRun before run')
            ->boolean($this->mock->isRun())
                ->isFalse()
        ;
        
        $this->assert('test Core\AppSystems\AbstractSystem::run and isRun after')
            ->variable($this->mock->run())
                ->isNull()
            ->boolean($this->mock->isRun())
                ->isTrue()
        ;
    }
}