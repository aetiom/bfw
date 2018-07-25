<?php

namespace BFW;

use \Exception;
use \BFW\Helpers\Constants;

/**
 * Application class
 * Manage all BFW application
 * Load and init components, modules, ...
 */
class Application
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
     * @const ERR_MEMCACHED_NOT_IMPLEMENT_INTERFACE Exception code the
     * memcache(d) class not implement the interface.
     */
    const ERR_MEMCACHED_NOT_IMPLEMENT_INTERFACE = 1301003;
    
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
     * @var \BFW\ModuleList $moduleList System who manage all modules
     */
    protected $moduleList;
    
    /**
     * @var \BFW\Core\Errors $errors System who manage personal errors page
     */
    protected $errors;
    
    /**
     * @var \BFW\Core\Cli $cli Cli system
     */
    protected $cli;
    
    /**
     * @var \BFW\SubjectList $subjectList System who manage subjects list
     */
    protected $subjectList;
    
    /**
     * @var \stdClass $ctrlRouterInfos Infos from router for controller system
     */
    protected $ctrlRouterInfos;
    
    /**
     * @var \BFW\Monolog $monolog Monolog system for bfw debug
     */
    protected $monolog;

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
     * @return \BFW\Application The current instance of this class
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            $calledClass = get_called_class(); //Autorize extends this class
            self::$instance = new $calledClass;
        }

        return self::$instance;
    }

    /**
     * Getter to access to cli property
     * 
     * @return \BFW\Core\Cli
     */
    public function getCli()
    {
        return $this->cli;
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
     * Getter to access to the errors instance
     * 
     * @return \BFW\Errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Getter to access to the ctrlRouterInfos property
     * 
     * @return null|\stdClass
     */
    public function getCtrlRouterInfos()
    {
        return $this->ctrlRouterInfos;
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
     * Getter to access to moduleList system
     * 
     * @return \BFW\ModuleList
     */
    public function getModuleList()
    {
        return $this->moduleList;
    }
    
    /**
     * Getter to access to a module
     * 
     * @param string $moduleName The module name to access
     * 
     * @return \BFW\Module
     */
    public function getModuleForName($moduleName)
    {
        return $this->moduleList->getModuleForName($moduleName);
    }
    
    /**
     * Getter to access to Monolog system
     * 
     * @return \BFW\Monolog
     */
    public function getMonolog()
    {
        return $this->monolog;
    }
    
    /**
     * Getter to access to the options system
     * 
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
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
     * Getter to access to the run step array
     * 
     * @return array
     */
    public function getRunSteps()
    {
        return $this->runSteps;
    }

    /**
     * Getter to access to the subjects list
     * 
     * @return \BFW\SubjectList
     */
    public function getSubjectList()
    {
        return $this->subjectList;
    }
    
    /**
     * Initialize all components
     * 
     * @param array $options Options passed to application
     * 
     * @return void
     */
    public function initSystem($options)
    {
        $this->initOptions($options);
        $this->initConstants();
        $this->initComposerLoader();
        $this->initSubjectList();
        $this->initConfig();
        $this->initMonolog();
        $this->initRequest();
        $this->initSession();
        $this->initErrors();
        $this->initCli();
        $this->initRunTasks();
        $this->initModuleList();
        
        $this->monolog->getLogger()->debug('Framework initializing done.');
        
        return $this;
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
        $this->options
            ->searchPaths()
            ->checkPaths()
        ;
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
     * Initialize the subjectList object
     * 
     * @return void
     */
    protected function initSubjectList()
    {
        $this->subjectList = new \BFW\SubjectList;
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
     * Initialize the property monolog with \BFW\Monolog instance
     * 
     * @return void
     */
    protected function initMonolog()
    {
        $this->monolog = new \BFW\Monolog('bfw', $this->config);
        $this->monolog->addAllHandlers('handlers', 'monolog.php');
        
        $this->monolog->getLogger()->debug(
            'Currently during the initialization framework step.'
        );
    }

    /**
     * Initialize request property with the \BFW\Request class
     * 
     * @return void
     */
    protected function initRequest()
    {
        $this->request = \BFW\Request::getInstance();
        $this->request->runDetect();
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
        $this->errors = new \BFW\Core\Errors;
    }

    /**
     * Initialize cli property with the \BFW\Core\Cli class
     * 
     * @return void
     */
    protected function initCli()
    {
        $this->cli = new \BFW\Core\Cli;
    }
    
    /**
     * Initialize taskers
     * 
     * @return void
     */
    protected function initRunTasks()
    {
        $stepsToRun = [];
        $closureNb  = 0;
        
        foreach ($this->runSteps as $step) {
            if ($step instanceof \Closure) {
                $stepName = 'closure_'.$closureNb;
                $closureNb++;
            } else {
                $stepName = $step[1];
            }
            
            //To keep methods to run protected
            $stepsToRun[$stepName] = (object) [
                'callback' => function() use ($step) {
                    $step();
                }
            ];
        }
        
        $runTasks = new \BFW\RunTasks($stepsToRun, 'BfwApp');
        $this->subjectList->addSubject($runTasks, 'ApplicationTasks');
    }

    /**
     * Initialize moduleList property with the \BFW\ModuleList class
     * 
     * @return void
     */
    protected function initModuleList()
    {
        $this->moduleList = new \BFW\ModuleList;
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
            [$this, 'loadAllModules'],
            [$this, 'runAllCoreModules'],
            [$this, 'runAllAppModules'],
            [$this, 'runCliFile'],
            [$this, 'initCtrlRouterLink'],
            [$this, 'runCtrlRouterLink']
        ];
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run()
    {
        $this->monolog->getLogger()->debug('running framework');
        
        $runTasks = $this->subjectList->getSubjectForName('ApplicationTasks');
        
        $runTasks->run();
        $runTasks->sendNotify('bfw_run_done');
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
        $memcachedConfig = $this->config->getValue('memcached', 'memcached.php');

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
        
        if (!($this->memcached instanceof \BFW\Memcache\MemcacheInterface)) {
            throw new Exception(
                'Memcache class '.$class.' not implement the interface.',
                $this::ERR_MEMCACHED_NOT_IMPLEMENT_INTERFACE
            );
        }
        
        $this->memcached->connectToServers();
    }

    /**
     * Read all directories in modules directory and add each module to Modules
     * class.
     * Generate the load tree.
     * Not initialize modules !
     * 
     * @return void
     */
    protected function loadAllModules()
    {
        $listModules = array_diff(scandir(MODULES_DIR), ['.', '..']);

        foreach ($listModules as $moduleName) {
            $modulePath = realpath(MODULES_DIR.$moduleName); //Symlink

            if (!is_dir($modulePath)) {
                continue;
            }

            $this->moduleList->addModule($moduleName);
        }

        $this->moduleList->readNeedMeDependencies();
        $this->moduleList->generateTree();
    }

    /**
     * Load core modules defined into config bfw file.
     * Only module for controller, router, database and template only.
     * 
     * @return void
     */
    protected function runAllCoreModules()
    {
        $moduleList = $this->config->getValue('modules', 'modules.php');
        foreach ($moduleList as $moduleInfos) {
            $moduleName    = $moduleInfos['name'];
            $moduleEnabled = $moduleInfos['enabled'];

            if (empty($moduleName) || $moduleEnabled === false) {
                continue;
            }

            $this->runModule($moduleName);
        }
    }

    /**
     * Load all modules (except core).
     * Get the load tree, read him and load all modules with the order
     * declared into the tree.
     * 
     * @return void
     */
    protected function runAllAppModules()
    {
        $tree = $this->moduleList->getLoadTree();

        foreach ($tree as $firstLine) {
            foreach ($firstLine as $secondLine) {
                foreach ($secondLine as $moduleName) {
                    $this->runModule($moduleName);
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
    protected function runModule($moduleName)
    {
        $this->subjectList->getSubjectForName('ApplicationTasks')
            ->sendNotify('BfwApp_run_module_'.$moduleName);
        
        $this->moduleList->getModuleForName($moduleName)->runModule();
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

        $this->subjectList->getSubjectForName('ApplicationTasks')
            ->sendNotify('run_cli_file');
        
        $fileToExec = $this->cli->obtainFileFromArg();
        $this->cli->run($fileToExec);
    }
    
    /**
     * Create a new observer to controller and router module.
     * 
     * @return void
     */
    protected function initCtrlRouterLink()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        //Others properties can be dynamically added by modules
        $this->ctrlRouterInfos = (object) [
            'isFound' => false,
            'forWho'  => null,
            'target'  => null,
            'datas'   => null
        ];
        
        $ctrlRouterTask = new RunTasks(
            $this->obtainCtrlRouterLinkTasks(),
            'ctrlRouterLink'
        );
        
        $this->subjectList->addSubject($ctrlRouterTask, 'ctrlRouterLink');
        
        $runTasks = $this->subjectList->getSubjectForName('ApplicationTasks');
        $runTasks->sendNotify('bfw_ctrlRouterLink_subject_added');
    }
    
    /**
     * List all tasks runned by ctrlRouterLink
     * 
     * @return array
     */
    protected function obtainCtrlRouterLinkTasks()
    {
        return [
            'searchRoute'     => (object) [
                'context' => $this->ctrlRouterInfos
            ],
            'checkRouteFound' => (object) [
                'callback' => function() {
                    if ($this->ctrlRouterInfos->isFound === false) {
                        http_response_code(404);
                    }
                }
            ],
            'execRoute'       => (object) [
                'context' => $this->ctrlRouterInfos
            ]
        ];
    }
    
    /**
     * Execute the ctrlRouter task to find the route and the controller.
     * If nothing is found (context object), return an 404 error.
     * Not executed in cli.
     * 
     * @return void
     */
    protected function runCtrlRouterLink()
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        
        $this->subjectList->getSubjectForName('ctrlRouterLink')->run();
    }
}
