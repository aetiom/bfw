<?php

namespace BFW\test\unit;

use \atoum;
use \BFW\test\unit\mocks\Config as MockConfig;

require_once(__DIR__.'/../../../../vendor/autoload.php');

class Config extends atoum
{
    /**
     * @var $class Class instance
     */
    protected $class;

    /**
     * Call before each test method
     * Define CONFIG_DIR constant
     * Instantiate the class
     * 
     * @param $testMethod string The name of the test method executed
     * 
     * @return void
     */
    public function beforeTestMethod($testMethod)
    {
        if (
            $testMethod === 'testSearchAllConfigsFilesWithDirectory' || 
            $testMethod === 'testGetValueExceptions' ||
            $testMethod === 'testGetConfigs' ||
            $testMethod === 'testSetConfigs'
        ) {
            return;
        }
        
        define('CONFIG_DIR', '');
        
        $this->class = new \BFW\Config('unit_test');
    }
    
    protected function addOverrideLoadPhpConfigFile(MockConfig $configMock)
    {
        $configMock->addOverridedMethod(
            'loadPhpConfigFile',
            function ($fileKey, $filePath) use ($configMock) {
                $debugValue = false;
                if (strpos($filePath, '/class/core/Options.php') !== false) {
                    $debugValue = true;
                }
                
                $configMock->forceConfig(
                    $fileKey,
                    (object) [
                        'debug' => $debugValue,
                        'errorRenderFct' => (object) [
                            'default' => '\BFW\Core\Errors::defaultErrorRender',
                            'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                        ],
                        'fixNullValue' => null
                    ]
                );
            }
        );
    }
    
    /**
     * test method for the case where there is no declared config file
     * 
     * @return void
     */
    public function testWhenNoFile()
    {
        $this->assert('test config with no file to found')
            ->if($this->class->loadFiles())
            ->given($config = $this->class)
            ->then
            ->exception(function() use ($config) {
                $config->getValue('test');
            })
                ->hasCode($config::ERR_FILE_NOT_FOUND)
                ->hasMessage('The file  has not been found for config test');
    }
    
    /**
     * test method for the case where there is a json config file
     * 
     * @return void
     */
    public function testWithJsonFile()
    {
        $configJson = '{
            "debug": false,
            "errorRenderFct": {
                "default": "\\\BFW\\\Core\\\Errors::defaultErrorRender",
                "cli": "\\\BFW\\\Core\\\Errors::defaultCliErrorRender"
            }
        }';
        
        $this->assert('test config with a good json file')
            ->if($this->function->file_exists = true)
            ->and($this->function->scandir = ['.', '..', 'test.json'])
            ->and($this->function->is_file = true)
            ->and($this->function->file_get_contents = $configJson)
            ->then
            ->given($this->class->loadFiles())
            ->boolean($this->class->getValue('debug'))
                ->isFalse()
            ->object($errorRenderFct = $this->class->getValue('errorRenderFct'))
            ->string($errorRenderFct->default)
                ->isEqualTo('\BFW\Core\Errors::defaultErrorRender')
            ->string($errorRenderFct->cli)
                ->isEqualTo('\BFW\Core\Errors::defaultCliErrorRender');
        
        $this->assert('test config with a bad json file')
            ->if($this->function->file_exists = true)
            ->and($this->function->scandir = ['.', '..', 'test.json'])
            ->and($this->function->is_file = true)
            ->and($this->function->file_get_contents = substr($configJson, 0, -1))
            ->then
            ->exception(function() {
                $config = new \BFW\Config('unit_test');
                $config->loadFiles();
            })
                ->hasCode(\BFW\Config::ERR_JSON_PARSE)
                ->hasMessage('Syntax error');
    }
    
    /**
     * test method for the case where there is a php config file
     * 
     * @return void
     */
    public function testWithPhpFile()
    {
        //$this->function->require : Doesn't work.
        
        $this->assert('test config with a good php file')
            ->if($this->function->file_exists = true)
            ->and($this->function->scandir = ['.', '..', 'test.php'])
            ->and($this->function->is_file = true)
            ->then
            ->if($config = new MockConfig('unit_test'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->boolean($config->getValue('debug'))
                ->isFalse()
            ->object($errorRenderFct = $config->getValue('errorRenderFct'))
            ->string($errorRenderFct->default)
                ->isEqualTo('\BFW\Core\Errors::defaultErrorRender')
            ->string($errorRenderFct->cli)
                ->isEqualTo('\BFW\Core\Errors::defaultCliErrorRender')
            ->variable($config->getValue('fixNullValue'))
                ->isNull();
    }
    
    /**
     * test method for the case where there is an unsupported config file
     * 
     * @return void
     */
    public function testForUnsupportedFileExt()
    {
        $this->assert('test config with a unsupported file extension')
            ->if($this->function->file_exists = true)
            ->and($this->function->scandir = ['.', '..', 'test.yml'])
            ->and($this->function->is_file = true)
            ->then
            ->if($this->class->loadFiles())
            ->given($config = $this->class)
            ->then
            ->exception(function() use ($config) {
                $config->getValue('test');
            })
                ->hasCode($config::ERR_FILE_NOT_FOUND)
                ->hasMessage('The file  has not been found for config test');
    }
    
    /**
     * test method for searchAllConfigsFiles when the file is a symlink
     * 
     * @return void
     */
    public function testSearchAllConfigsFilesWithLinkedFile()
    {
        $this->assert('test searchAllConfigsFiles for a linked file')
            ->if($this->function->file_exists = true)
            ->and($this->function->scandir = ['.', '..', 'test.json'])
            ->and($this->function->is_file = false)
            ->and($this->function->is_link = true)
            ->and($this->function->realpath = '/tmp/test.json')
            ->and($this->function->file_get_contents = '{"debug": false}')
            ->then
            ->given($this->class->loadFiles())
            ->boolean($this->class->getValue('debug'))
                ->isFalse();
    }
    
    /**
     * test method for searchAllConfigsFiles when it is a directory
     * 
     * @return void
     */
    public function testSearchAllConfigsFilesWithDirectory()
    {
        $this->assert('test searchAllConfigsFiles for a directory')
            ->given(define('CONFIG_DIR', __DIR__.'/../'))
            ->and($config = new MockConfig('class'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->boolean($config->getValue('debug', 'core/Options.php'))
                ->isTrue()
            ->boolean($config->getValue('debug', 'core/Errors.php'))
                ->isFalse();
    }
    
    /**
     * test method for getValue() exception messages
     * 
     * @return void
     */
    public function testGetValueExceptions()
    {
        define('CONFIG_DIR', __DIR__.'/../');
        
        $this->assert('test getValue exception no file specified')
            ->if($config = new MockConfig('class'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->exception(function() use ($config) {
                $config->getValue('debug');
            })
                ->hasCode($config::ERR_GETVALUE_FILE_NOT_INDICATED)
                ->hasMessage(
                    'There are many config files. '
                    .'Please indicate the file to obtain the config debug'
                );
        
        $this->assert('test getValue exception unknown key')
            ->if($config = new MockConfig('class'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->exception(function() use ($config) {
                $config->getValue('bulton', 'core/Options.php');
            })
                ->hasCode($config::ERR_KEY_NOT_FOUND)
                ->hasMessage('The config key bulton has not been found');
    }
    
    /**
     * Test method for getConfig() and getConfigForFile()
     */
    public function testGetConfigs()
    {
        $this->assert('test getConfigs with multiple files')
            ->given(define('CONFIG_DIR', __DIR__.'/../'))
            ->and($config = new MockConfig('class/core'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->array($config->getConfig())
                ->isEqualTo([
                    'Errors.php' => (object) [
                        'debug'          => false,
                        'errorRenderFct' => (object) [
                            'default' => '\BFW\Core\Errors::defaultErrorRender',
                            'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                        ],
                        'fixNullValue'   => null
                    ],
                    'Options.php' => (object) [
                        'debug'          => true,
                        'errorRenderFct' => (object) [
                            'default' => '\BFW\Core\Errors::defaultErrorRender',
                            'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                        ],
                        'fixNullValue'   => null
                    ]
                ])
            ->object($config->getConfigForFile('Options.php'))
                ->isEqualTo((object) [
                    'debug'          => true,
                    'errorRenderFct' => (object) [
                        'default' => '\BFW\Core\Errors::defaultErrorRender',
                        'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                    ],
                    'fixNullValue'   => null
                ]);
    }
    
    /**
     * Test method for setConfigForFile() and setConfigKeyForFile()
     */
    public function testSetConfigs()
    {
        $this->assert('test setConfigs with multiple files')
            ->given(define('CONFIG_DIR', __DIR__.'/../'))
            ->and($config = new MockConfig('class/core'))
            ->and($this->addOverrideLoadPhpConfigFile($config))
            ->and($config->loadFiles())
            ->then
            ->object($config->getConfigForFile('Options.php'))
                ->isEqualTo((object) [
                    'debug'          => true,
                    'errorRenderFct' => (object) [
                        'default' => '\BFW\Core\Errors::defaultErrorRender',
                        'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                    ],
                    'fixNullValue'   => null
                ])
            ->then
            ->if($config->setConfigKeyForFile(
                'Options.php',
                'debug',
                false
            ))
            ->then
            ->object($config->getConfigForFile('Options.php'))
                ->isEqualTo((object) [
                    'debug'          => false,
                    'errorRenderFct' => (object) [
                        'default' => '\BFW\Core\Errors::defaultErrorRender',
                        'cli'     => '\BFW\Core\Errors::defaultCliErrorRender'
                    ],
                    'fixNullValue'   => null
                ])
            ->then
            ->if($config->setConfigForFile('Options.php', ['debug' => true]))
            ->then
            ->array($config->getConfigForFile('Options.php'))
                ->isEqualTo([
                    'debug' => true
                ])
        ;
    }
}
