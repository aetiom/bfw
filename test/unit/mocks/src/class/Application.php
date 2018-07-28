<?php

namespace BFW\Test\Mock;

//To be included by module who use it
require_once(__DIR__.'/core/Cli.php');
require_once(__DIR__.'/core/Errors.php');
require_once(__DIR__.'/ModuleList.php');

class Application extends \BFW\Application
{
    /**
     * @var array $mockedConfigValues The values to use for config
     */
    protected $mockedConfigValues = [];
    
    /**
     * @var \stdClass[] $mockedModulesList List of fake module to load, the
     * value is an object with properties "config" and "loadInfos" used to
     * declare the fake module.
     */
    protected $mockedModulesList = [];
    
    /**
     * Setter to parent property runSteps
     * 
     * @param callable[] $runSteps The new runSteps value
     * 
     * @return $this
     */
    public function setRunSteps($runSteps)
    {
        $this->runSteps = $runSteps;
        return $this;
    }
    
    /**
     * Getter to property mockedConfigValues
     * 
     * @return array|object
     */
    public function getMockedConfigValues()
    {
        return $this->mockedConfigValues;
    }
    
    /**
     * Setter to property mockedConfigValues
     * 
     * @param string $filename
     * @param array|object $mockedConfigValues
     * 
     * @return $this
     */
    public function setMockedConfigValues($filename, $mockedConfigValues)
    {
        $this->mockedConfigValues[$filename] = $mockedConfigValues;
        return $this;
    }
    
    /**
     * Getter to property mockedModulesList
     * 
     * @return \stdClass[]
     */
    public function getMockedModulesList()
    {
        return $this->mockedModulesList;
    }
    
    /**
     * Add a new fake module to the list
     * 
     * @param string $moduleName The name of the module
     * @param \stdClass $mockedModulesInfos An object with properties "config"
     * and "loadInfos" used to declare the fake module.
     * 
     * @return $this
     */
    public function addMockedModulesList(
        $moduleName,
        \stdClass $mockedModulesInfos
    ) {
        $this->mockedModulesList[$moduleName] = $mockedModulesInfos;
        return $this;
    }
    
    /**
     * {@inheritdoc}
     * Define the Config object and use the value of the property
     * mockedConfigValues to set a fake config.
     * If mockedConfigValues is null, read the default config file and set
     * the property value with it.
     */
    protected function initConfig()
    {
        if ($this->mockedConfigValues === null) {
            $configList = [
                'errors.php',
                'global.php',
                'memcached.php',
                'modules.php',
                'monolog.php'
            ];
            
            foreach ($configList as $configFilename) {
                $this->mockedConfigValues[$configFilename] = require(
                    $this->options->getValue('vendorDir')
                    .'/bulton-fr/bfw/skel/app/config/bfw/'.$configFilename
                );
            }
        }
        
        $this->config = new \BFW\Config('bfw');
        foreach ($this->mockedConfigValues as $configFilename => $configValues) {
            $this->config->setConfigForFilename(
                $configFilename,
                $configValues
            );
        }
    }
    
    /**
     * {@inheritdoc}
     * Use the mocked class
     */
    protected function initCli()
    {
        $this->cli = new \BFW\Core\Test\Mock\Cli;
    }
    
    /**
     * {@inheritdoc}
     * Use the mocked class
     */
    protected function initErrors()
    {
        $this->errors = new \BFW\Core\Test\Mock\Errors;
    }
    
    /**
     * {@inheritdoc}
     * Use the mocked class
     */
    protected function initModuleList()
    {
        $this->moduleList = new \BFW\Test\Mock\ModuleList;
    }
    
    /**
     * {@inheritdoc}
     * Use the property mockedModulesList to declare all fake modules before
     * call the parent method.
     */
    protected function loadAllModules()
    {
        $moduleList = $this->moduleList;
        foreach($this->mockedModulesList as $moduleName => $module) {
            $moduleList::setModuleConfig($moduleName, $module->config);
            $moduleList::setModuleLoadInfos($moduleName, $module->loadInfos);
        }
        
        parent::loadAllModules();
    }
}
