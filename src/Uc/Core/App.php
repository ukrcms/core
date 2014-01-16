<?php

  namespace Uc\Core;

  /**
   * Application class
   *
   * @author  Ivan Scherbak <dev@funivan.com>
   */
  class App extends Component {

    /**
     * @var \Uc\Core\Language
     */
    public $language = null;

    /**
     *
     * @var \Uc\Core\Url
     */
    public $url = null;

    /**
     *
     * @var \Uc\Core\Config
     */
    public $config = null;

    /**
     *
     * @var \Uc\Core\Theme
     */
    public $theme = null;

    /**
     *
     * @var \Uc\Core\Db
     */
    public $db = null;

    /**
     *
     * @var \Uc\Core\User\Identity
     */
    public $userIdentity = null;

    /**
     * Params of application
     *
     * @var array
     */
    public $params = array();

    /**
     *
     * @var \Uc\Core\Controller
     */
    public $controller = null;

    /**
     * Used for more flexible of controller names
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @var string
     */
    private $controllerPrefix = '';

    /**
     * Used for more flexible of controller names
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @var string
     */
    private $controllerPostfix = 'Controller';

    /**
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @var string
     */
    private $actionPrefix = 'action';

    /**
     *
     * @var string
     */
    private $actionPostfix = '';

    /**
     * Set components of application
     * Init components
     *
     * @param array $components
     * @throws Exception
     * @return \Uc\Core\App
     */
    public function __construct($components = array()) {

      foreach ($components as $componentName => $options) {
        if (is_array($options) and !empty($options['class'])) {
          $className = $options['class'];
          unset($options['class']);
        } else {
          $className = '\\Uc\\Core\\' . ucfirst($componentName);
        }

        /** @var $component Component */
        $component = new $className();
        # set options
        if (is_array($options)) {
          foreach ($options as $key => $value) {
            $component->$key = $value;
          }
        } else {
          throw new Exception('Invalid component config. Must be array. Component #' . $componentName);
        }

        $component->init();

        $this->$componentName = $component;
      }

      if (empty($this->config)) {
        throw new Exception('Config component must be defined');
      }

      if ($this->config->isDevIp()) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
      }

      return $this->init();
    }

    /**
     * Run Application
     *
     * @throws \Exception
     */
    public function run() {
      try {

        $controllerRoute = $this->url->getControllerRoute();

        if (!$controllerRoute) {
          throw new \Uc\Core\Exception('Controller route is empty');
        }

        $actionRoute = $this->url->getActionRoute();

        if (!$actionRoute) {
          throw new \Uc\Core\Exception('Action route is empty');
        }

        # get names of controller and action
        $controllerRealName = $this->getControllerClassName($controllerRoute);
        $actionRelName = $this->getActionName($actionRoute);

        if (!class_exists($controllerRealName)) {
          throw new \Uc\Core\Exception('Controller class #' . $controllerRealName . ' does not exist');
        }

        $this->controller = new $controllerRealName();

        # validation of action
        if (!is_callable(array($this->controller, $actionRelName))) {
          throw new \Uc\Core\Exception('Action #' . $actionRelName . ' in controller #' . $controllerRealName . ' can not be call');
        }

        return $this->controller->$actionRelName();
      } catch (\Exception $exception) {

        $errorAction = (!empty($this->config) and !empty($this->config->errorAction)) ? $this->config->errorAction : null;

        if (!empty($errorAction)) {
          $errorControllerAction = \Uc::app()->url->getControllerActionFromRoute($this->config->errorAction);
        }

        if (!empty($errorControllerAction)) {
          try {
            $controllerClass = $this->getControllerClassName($errorControllerAction[0]);
            $actionRoute = $this->getActionName($errorControllerAction[1]);
            $this->controller = new $controllerClass();
            return $this->controller->$actionRoute($exception);
          } catch (\Exception $e) {
            throw $e;
          }
        }
      }
      return;
    }

    /**
     *
     * @author   Ivan Scherbak <dev@funivan.com>
     * @param string $route Controller route for ex: ub/site/controller
     * @return string
     */
    protected function getControllerClassName($route) {
      $route = implode('\\', array_map("ucfirst", explode('/', $route)));
      return '\\' . $this->controllerPrefix . $route . '\\' . $this->controllerPostfix;
    }

    /**
     *
     * @author  Ivan Scherbak <dev@funivan.com>
     * @param string $name Action name for ex: login
     * @return string
     */
    protected function getActionName($name) {
      return $controllerActionName = $this->actionPrefix . ucfirst($name) . $this->actionPostfix;
    }

    /**
     *
     * @param string $controllerClassName
     * @return string
     */
    public function getControllerName($controllerClassName) {
      return preg_replace('!^' . $this->controllerPrefix . '(.*)' . $this->controllerPostfix . '$!', '$1', $controllerClassName);
    }

    /**
     * @return array
     */
    public static function getDebugInfo() {
      $runTime = $useMemory = 0;
      if (defined('UC_START_TIME')) {
        $runTime = round(microtime(true) - UC_START_TIME, 5);
      }
      if (defined('UC_START_MEMORY')) {
        $useMemory = round((memory_get_usage() - UC_START_MEMORY) / 1024 / 1024, 5);
      }

      return array(
        $runTime,
        $useMemory,
      );
    }

  }