<?php

namespace BFW;

use \Exception;
use \BFW\Helpers\Constants;

/**
 * Application class
 * Manage all BFW application
 * Load and init components, modules, ...
 */
class Application extends Subjects
{
    /**
     * @const ERR_MEMCACHED_NOT_CLASS_DEFINED Exception code if memcache(d) is
     * enabled but the class to use is not defined.
     */
    const ERR_MEMCACHED_NOT_CLASS_DEFINED = 1301001;
    
    /**
     * @const ERR_MEMCACHED_CLASS_NOT_FOUND Exception code if the memcache(d)
     * class is not found.
     */
    const ERR_MEMCACHED_CLASS_NOT_FOUND = 1301002;
    
    /**
     * @const ERR_CLI_NO_FILE_SPECIFIED Exception code if the cli file to run
     * is not specified
     */
    const ERR_CLI_NO_FILE_SPECIFIED = 1301003;
    
    /**
     * @const ERR_CLI_FILE_NOT_FOUND Exception code if the cli file to run is
     * not found.
     */
    const ERR_CLI_FILE_NOT_FOUND = 1301004;
    
    /**
     * @var \BFW\Application|null $instance Application instance (Singleton)
     */
    protected static $instance = null;

    /**
     * @var string $rootDir Path to the application project directory
     */
    protected $rootDir = '';

    /**
     * @var \BFW\Config $config Config's instance for BFW
     */
    protected $config;

    /**
     * @var \BFW\Core\Options $options Option's instance for the core
     */
    protected $options;

    /**
     * @var \Composer\Autoload\ClassLoader $composerLoader Loader used by
     *  composer.
     */
    protected $composerLoader;

    /**
     * @var array[] $runSteps All steps used for run the application
     */
    protected $runSteps = [];

    /**
     * @var Object $memcached The class used to connect to memcache(d) server.
     * The class name should be declared into config file.
     */
    protected $memcached;

    /**
     * @var \BFW\Request $request Informations about the http request
     */
    protected $request;

    /**
     * @var \BFW\Modules $modules System who manage all modules
     */
    protected $modules;
    
    /**
     * @var \BFW\Core\Errors $errors System who manage personal errors page
     */
    protected $errors;

    /**
     * Constructor
     * Init output buffering
     * Declare run steps
     * Set UTF-8 header
     * 
     * protected for Singleton pattern
     */
    protected function __construct()
    {
        //Start the output buffering
        ob_start();

        $this->declareRunSteps();

        //Defaut http header. Define here add possiblity to override him
        header('Content-Type: text/html; charset=utf-8');
        
        //Default charset to UTF-8. Define here add possiblity to override him
        ini_set('default_charset', 'UTF-8');
    }

    /**
     * Get the Application instance (Singleton pattern)
     * 
     * @param array $options Options passed to application
     * 
     * @return \BFW\Application The current instance of this class
     */
    public static function getInstance($options = [])
    {
        if (self::$instance === null) {
            $calledClass = get_called_class(); //Autorize extends this class
            self::$instance = new $calledClass;
            self::$instance->initSystem($options);
        }

        return self::$instance;
    }

    /**
     * Like getInstance. This is to have a keyword easier for users who want
     * initialize the application
     * 
     * @param array $options Options passed to application
     * 
     * @return \BFW\Application The current instance of this class
     */
    public static function init($options = [])
    {
        $calledClass = get_called_class(); //Autorize extends this class
        return $calledClass::getInstance($options);
    }

    /**
     * Getter to access to composerLoader property
     * 
     * @return \Composer\Autoload\ClassLoader The composer class loader
     */
    public function getComposerLoader()
    {
        return $this->composerLoader;
    }

    /**
     * Getter to access to the config instance
     * 
     * @return \BFW\Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * Getter to access to memcache instance
     * 
     * @return Object|null
     */
    public function getMemcached()
    {
        return $this->memcached;
    }
    
    /**
     * Getter to access to a module
     * 
     * @param string $moduleName The module name to access
     * 
     * @return \BFW\Module
     */
    public function getModule($moduleName)
    {
        return $this->modules->getModule($moduleName);
    }

    /**
     * Getter to access to an option's value
     * 
     * @param string $optionKey The key for the option
     * 
     * @return mixed
     */
    public function getOption($optionKey)
    {
        return $this->options->getValue($optionKey);
    }
    
    /**
     * Getter to access to the Request instance
     * 
     * @return \BFW\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Initialize all components
     * 
     * @param array $options Options passed to application
     * 
     * @return void
     */
    protected function initSystem($options)
    {
        $this->initOptions($options);
        $this->initConstants();
        $this->initComposerLoader();
        $this->initConfig();
        $this->initRequest();
        $this->initSession();
        $this->initErrors();
        $this->initModules();
    }

    /**
     * Initialize options with the class \BFW\Core\Options
     * 
     * @param array $options The option passed when initialize this class
     */
    protected function initOptions($options)
    {
        $defaultOptions = [
            'rootDir'    => null,
            'vendorDir'  => null,
            'runSession' => true
        ];

        $this->options = new \BFW\Core\Options($defaultOptions, $options);
    }

    /**
     * Initialize all constants used by framework
     * Use helper Constants::create to allow override of constants
     * 
     * @return void
     */
    protected function initConstants()
    {
        Constants::create('ROOT_DIR', $this->options->getValue('rootDir'));

        Constants::create('APP_DIR', ROOT_DIR.'app/');
        Constants::create('SRC_DIR', ROOT_DIR.'src/');
        Constants::create('WEB_DIR', ROOT_DIR.'web/');

        Constants::create('CONFIG_DIR', APP_DIR.'config/');
        Constants::create('MODULES_DIR', APP_DIR.'modules/');

        Constants::create('CLI_DIR', SRC_DIR.'cli/');
        Constants::create('CTRL_DIR', SRC_DIR.'controllers/');
        Constants::create('MODELES_DIR', SRC_DIR.'modeles/');
        Constants::create('VIEW_DIR', SRC_DIR.'view/');
    }

    /**
     * Initialize composer loader
     * Obtain the composerLoader instance
     * Call addComposerNamespaces method to add Application namespaces
     * 
     * @return void
     */
    protected function initComposerLoader()
    {
        $this->composerLoader = require(
            $this->options->getValue('vendorDir').'autoload.php'
        );
        $this->addComposerNamespaces();
    }

    /**
     * Initialize the property config with \BFW\Config instance
     * The config class will search all file in "bfw" directory and load files
     * 
     * @return void
     */
    protected function initConfig()
    {
        $this->config = new \BFW\Config('bfw');
        $this->config->loadFiles();
    }

    /**
     * Initialize request property with the \BFW\Request class
     * 
     * @return void
     */
    protected function initRequest()
    {
        $this->request = \BFW\Request::getInstance();
    }

    /**
     * Initiliaze php session if option "runSession" is not (bool) false
     * 
     * @return void
     */
    protected function initSession()
    {
        if ($this->options->getValue('runSession') === false) {
            return;
        }

        //Destroy session cookie if browser quit
        session_set_cookie_params(0);

        //Run session
        session_start();
    }

    /**
     * Initialize errors property with the \BFW\Core\Errors class
     * 
     * @return void
     */
    protected function initErrors()
    {
        $this->errors = new \BFW\Core\Errors();
    }

    /**
     * Initialize modules property with the \BFW\Modules class
     * 
     * @return void
     */
    protected function initModules()
    {
        $this->modules = new \BFW\Modules;
    }

    /**
     * Add namespaces used by a BFW Application to composer
     * 
     * @return void
     */
    protected function addComposerNamespaces()
    {
        $this->composerLoader->addPsr4('Controller\\', CTRL_DIR);
        $this->composerLoader->addPsr4('Modules\\', MODULES_DIR);
        $this->composerLoader->addPsr4('Modeles\\', MODELES_DIR);
    }

    /**
     * Declare all steps to run the application
     * 
     * @return void
     */
    protected function declareRunSteps()
    {
        $this->runSteps = [
            [$this, 'loadMemcached'],
            [$this, 'readAllModules'],
            [$this, 'loadAllCoreModules'],
            [$this, 'loadAllAppModules'],
            [$this, 'runCliFile']
        ];
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run()
    {
        foreach ($this->runSteps as $action) {
            $action();

            $notifyAction = $action;
            if (is_array($action)) {
                $notifyAction = $action[1];
            }

            $this->notifyAction('apprun_'.$notifyAction);
        }

        $this->notifyAction('bfw_run_finish');
    }

    /**
     * Connect to memcache(d) server with the class declared in config file
     * 
     * @return Object
     * 
     * @throws Exception If memcached is enabled but no class is define. Or if
     *  The class declared into the config is not found.
     */
    protected function loadMemcached()
    {
        $memcachedConfig = $this->config->getValue('memcached');

        if ($memcachedConfig['enabled'] === false) {
            return;
        }

        $class = $memcachedConfig['class'];
        if (empty($class)) {
            throw new Exception(
                'Memcached is active but no class is define',
                $this::ERR_MEMCACHED_NOT_CLASS_DEFINED
            );
        }

        if (class_exists($class) === false) {
            throw new Exception(
                'Memcache class '.$class.' not found.',
                $this::ERR_MEMCACHED_CLASS_NOT_FOUND
            );
        }

        $this->memcached = new $class;
    }

    /**
     * Read all directories in modules directory and add each module to Modules
     * class.
     * Generate the load tree.
     * Not initialize modules !
     * 
     * @return void
     */
    protected function readAllModules()
    {
        $listModules = array_diff(scandir(MODULES_DIR), ['.', '..']);

        foreach ($listModules as $moduleName) {
            $modulePath = realpath(MODULES_DIR.$moduleName); //Symlink

            if (!is_dir($modulePath)) {
                continue;
            }

            $this->modules->addModule($moduleName);
        }

        $this->modules->readNeedMeDependencies();
        $this->modules->generateTree();
    }

    /**
     * Load core modules defined into config bfw file.
     * Only module for controller, router, database and template only.
     * 
     * @return void
     */
    protected function loadAllCoreModules()
    {
        foreach ($this->config->getValue('modules') as $moduleInfos) {
            $moduleName    = $moduleInfos['name'];
            $moduleEnabled = $moduleInfos['enabled'];

            if (empty($moduleName) || $moduleEnabled === false) {
                continue;
            }

            $this->loadModule($moduleName);
        }
    }

    /**
     * Load all modules (except core).
     * Get the load tree, read him and load all modules with the order
     * declared into the tree.
     * 
     * @return void
     */
    protected function loadAllAppModules()
    {
        $tree = $this->modules->getLoadTree();

        foreach ($tree as $firstLine) {
            foreach ($firstLine as $secondLine) {
                foreach ($secondLine as $moduleName) {
                    $this->loadModule($moduleName);
                }
            }
        }
    }

    /**
     * Load a module
     * 
     * @param string $moduleName The module's name to load
     * 
     * @return void
     */
    protected function loadModule($moduleName)
    {
        $this->notifyAction('load_module_'.$moduleName);
        $this->modules->getModule($moduleName)->runModule();
    }

    /**
     * Run the cli file if we're in cli mode
     * 
     * @return void
     * 
     * @throws Exception If no file is specified or if the file not exist.
     */
    protected function runCliFile()
    {
        if (PHP_SAPI !== 'cli') {
            return;
        }

        $cliArgs = getopt('f:');
        if (!isset($cliArgs['f'])) {
            throw new Exception(
                'Error: No file specified.',
                $this::ERR_CLI_NO_FILE_SPECIFIED
            );
        }

        $file = $cliArgs['f'];
        if (!file_exists(CLI_DIR.$file.'.php')) {
            throw new Exception(
                'File to execute not found.',
                $this::ERR_CLI_FILE_NOT_FOUND
            );
        }

        $fctRunCliFile = function() use ($file) {
            require_once(CLI_DIR.$file.'.php');
        };

        $this->notifyAction('run_cli_file');
        $fctRunCliFile();
    }
}
