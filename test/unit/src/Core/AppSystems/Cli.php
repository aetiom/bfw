<?php

namespace BFW\Core\AppSystems\test\unit;

use \atoum;

require_once(__DIR__.'/../../../../../vendor/autoload.php');

/**
 * @engine isolate
 */
class Cli extends atoum
{
    use \BFW\Test\Helpers\Application;
    
    protected $mock;
    
    public function beforeTestMethod($testMethod)
    {
        $this->mockGenerator
            ->makeVisible('runCliFile')
        ;
        
        if ($testMethod === 'testConstructor') {
            return;
        }
        
        if (
            $testMethod === 'testRunCliFile' ||
            $testMethod === 'testRunCliFileWhenNotCli'
        ) {
            $this->mock = new \mock\BFW\Test\Mock\Core\AppSystems\Cli;
            
            $this->setRootDir(__DIR__.'/../../../../..');
            $this->createApp();
            $this->initApp();
        } else {
            $this->mock = new \mock\BFW\Core\AppSystems\Cli;
        }
    }
    
    public function testConstructor()
    {
        $this->assert('test Core\AppSystems\Cli::__construct')
            ->given($this->mock = new \mock\BFW\Core\AppSystems\Cli)
            ->object($this->mock->getCli())
                ->isInstanceOf('\BFW\Core\Cli')
        ;
    }
    
    public function testInvoke()
    {
        $this->assert('test Core\AppSystems\Cli::__invoke')
            ->object($this->mock->__invoke())
                ->isIdenticalTo($this->mock->getCli())
        ;
    }
    
    public function testToRun()
    {
        $this->assert('test Core\AppSystems\Cli::toRun')
            ->boolean($this->mock->toRun())
                ->isTrue()
        ;
    }
    
    public function testRunAndIsRun()
    {
        $this->assert('test Core\AppSystems\Cli::isRun before run')
            ->boolean($this->mock->isRun())
                ->isFalse()
        ;
        
        $this->assert('test Core\AppSystems\Cli::run and isRun after')
            ->and($this->calling($this->mock)->runCliFile = null)
            ->variable($this->mock->run())
                ->isNull()
            ->boolean($this->mock->isRun())
                ->isTrue()
            ->mock($this->mock)
                ->call('runCliFile')
                    ->once()
        ;
    }
    
    public function testRunCliFile()
    {
        $this->assert('test Core\AppSystems\Cli::runCliFile')
            ->given($observer = new \BFW\Test\Helpers\ObserverArray)
            ->and(
                \BFW\Application::getInstance()
                    ->getSubjectList()
                    ->getSubjectByName('ApplicationTasks')
                    ->attach($observer)
            )
            ->then
            
            ->given($cli = $this->mock->getCli())
            ->if($cli->setUseArgToObtainFile(false))
            ->and($cli->setFileInArg(CLI_DIR.'exemple.php'))
            ->then
            
            ->variable($this->invoke($this->mock)->runCliFile())
                ->isNull()
            ->string($observer->getActionReceived()[0])
                ->isEqualTo('run_cli_file')
            ->boolean($cli->getIsExecuted())
                ->isTrue()
        ;
    }
    
    public function testRunCliFileWhenNotCli()
    {
        $this->assert('test Core\AppSystems\Cli::runCliFile')
            ->if($this->constant->PHP_SAPI = 'www')
            ->then
            
            ->given($observer = new \BFW\Test\Helpers\ObserverArray)
            ->and(
                \BFW\Application::getInstance()
                    ->getSubjectList()
                    ->getSubjectByName('ApplicationTasks')
                    ->attach($observer)
            )
            ->then
            
            ->variable($this->invoke($this->mock)->runCliFile())
                ->isNull()
            ->array($observer->getActionReceived())
                ->isEmpty()
        ;
    }
}