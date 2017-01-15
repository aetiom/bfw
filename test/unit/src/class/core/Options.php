<?php

namespace BFW\Core\test\unit;
use \atoum;

require_once(__DIR__.'/../../../../../vendor/autoload.php');

class Options extends atoum
{
    /**
     * @var $class : Instance de la class
     */
    protected $class;
    
    /**
     * @var $defaultOptions : Les options par défaut pour le constructeur
     */
    protected $defaultOptions = [
        'rootDir'    => null,
        'vendorDir'  => null,
        'runSession' => true
    ];
    
    public function testOptionsDeclareDirectoriesWithSlashes()
    {
        $options = [
            'rootDir'   => '/tmp/bfw/v3/rootDir/',
            'vendorDir' => '/tmp/bfw/v3/vendorDir/'
        ];
        
        $this->assert('test Core\Options with rootDir and vendorDir declared')
            ->if($this->class = new \BFW\Core\Options($this->defaultOptions, $options))
            ->then
            ->string($this->class->getOption('rootDir'))
                ->isEqualTo('/tmp/bfw/v3/rootDir/')
            ->string($this->class->getOption('vendorDir'))
                ->isEqualTo('/tmp/bfw/v3/vendorDir/');
    }
    
    public function testOptionsDeclareDirectoriesWithoutSlashes()
    {
        $options = [
            'rootDir'   => '/tmp/bfw/v3/rootDir',
            'vendorDir' => '/tmp/bfw/v3/vendorDir'
        ];
        
        $this->assert('test Core\Options with rootDir and vendorDir declared')
            ->if($this->class = new \BFW\Core\Options($this->defaultOptions, $options))
            ->then
            ->string($this->class->getOption('rootDir'))
                ->isEqualTo('/tmp/bfw/v3/rootDir/')
            ->string($this->class->getOption('vendorDir'))
                ->isEqualTo('/tmp/bfw/v3/vendorDir/');
    }
    
    public function testOptionsFindDirectories()
    {
        $composerLoader = require(__DIR__.'/../../../../../vendor/autoload.php');
        $classPath      = realpath($composerLoader->findFile('\BFW\Core\Options'));
        $classDirPath   = str_replace('/Options.php', '', $classPath);
        
        echo '__FILE__: ';
        print_r(__FILE__);
        
        echo '$classPath: ';
        print_r($classPath);
        
        echo '$classDirPath: ';
        print_r($classDirPath);
        
        echo '$composerLoader->getPrefixes(): ';
        print_r($composerLoader->getPrefixes());
        
        echo '$composerLoader->getPrefixesPsr4(): ';
        print_r($composerLoader->getPrefixesPsr4());
        
        echo '$composerLoader->getClassMap(): ';
        print_r($composerLoader->getClassMap());
        
        $explodeClassDirPath = explode('/', $classDirPath);
        $countExplodeClassDirPath = count($explodeClassDirPath);
        
        unset(
            $explodeClassDirPath[$countExplodeClassDirPath],
            $explodeClassDirPath[$countExplodeClassDirPath-1],
            $explodeClassDirPath[$countExplodeClassDirPath-2],
            $explodeClassDirPath[$countExplodeClassDirPath-3],
            $explodeClassDirPath[$countExplodeClassDirPath-4]
        );
        $expectedVendorDir = implode('/', $explodeClassDirPath).'/';
        
        unset($explodeClassDirPath[$countExplodeClassDirPath-5]);
        $expectedRootDir = implode('/', $explodeClassDirPath).'/';

        echo '$expectedRootDir: ';
        print_r($expectedRootDir);
        echo '$expectedVendorDir: ';
        print_r($expectedVendorDir);
        
        $this->assert('test Core\Options with rootDir and vendorDir declared')
            ->if($this->class = new \BFW\Core\Options($this->defaultOptions, []))
            ->then
            ->string($this->class->getOption('rootDir'))
                ->isEqualTo($expectedRootDir)
            ->string($this->class->getOption('vendorDir'))
                ->isEqualTo($expectedVendorDir);
    }
}
