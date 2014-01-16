<?php

  namespace Uc\Core;

  /**
   * @author  Ivan Scherbak <dev@funivan.com> 7/20/12
   */
  class Url extends Component {

    /**
     *
     *
     * @var array
     */
    public $rules = array();

    /**
     * @var string
     */
    public $baseUrl = '';

    /**
     * @var string
     */
    protected $protocol = '';

    /**
     * @var string
     */
    protected $hostName = '';

    /**
     * @var string
     */
    protected $requestUrl = '';

    /**
     * @var string
     */
    protected $requestLangUrl = '';

    /**
     * @var string
     */
    protected $requestPath = '';

    /**
     *
     * @var boolean
     */
    private $urlIsParsed = false;

    /**
     *
     * @var string
     */
    private $controllerRoute = '';

    /**
     *
     * @var string
     */
    private $actionRoute = '';

    /**
     *
     * @var string
     */
    private $route = '';

    /**
     *
     * @var array
     */
    private $params = array();

    /**
     * @return $this
     */
    public function init() {

      if (empty($this->protocol) and !empty($_SERVER['SERVER_PROTOCOL'])) {
        $this->protocol = strtolower(preg_replace('!/(.*)$!', '', $_SERVER['SERVER_PROTOCOL']));
      }

      if (empty($this->hostName) and !empty($_SERVER[strtoupper($this->protocol) . '_HOST'])) {
        $this->hostName = $_SERVER[strtoupper($this->protocol) . '_HOST'];
      }

      return parent::init();
    }

    /**
     *
     * @return string
     */
    public function getControllerRoute() {
      $this->parseUrl();
      return $this->controllerRoute;
    }

    /**
     *
     * @throws \Exception
     * @return boolean
     */
    protected function parseUrl() {
      if ($this->urlIsParsed) {
        return true;
      }

      if (empty($this->protocol) or empty($this->hostName)) {
        throw new \Uc\Core\Exception('Please set $hostName and $protocol in class ' . get_class($this));
      }

      if (empty($_SERVER['REQUEST_URI'])) {
        throw new \Uc\Core\Exception('Empty REQUEST_URI');
      }

      if (!empty($this->rules)) {

        $this->parseLang();

        $queryString = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        $this->requestPath = str_replace('?' . $queryString, '', $this->requestUrl);

        foreach ($this->rules as $routeRegex => $routeAction) {
          # Prepare regexp Url
          $regex = '!^' . $routeRegex . '[/]{0,1}(\?.*|)$!U';
          $regex = preg_replace('!<([^:]+)>!U', '<$1:.*>', $regex);
          $regex = preg_replace('!<([^:]+):([^>]+)>!U', '(?P<$1>$2)', $regex);
          if (preg_match($regex, $this->requestUrl, $match)) {
            foreach ($match as $k => $v) {
              if (is_int($k)) {
                unset($match[$k]);
              }
            }

            $match = array_merge(array_filter($match), $_GET);
            if ($match) {
              $this->params = $match;
            }
            if (strpos($routeAction, '<') !== false) {
              foreach ($match as $key => $value) {
                $routeAction = str_replace('<' . $key . '>', $value, $routeAction);
              }
            }

            $controllerActionNamesArray = $this->getControllerActionFromRoute($routeAction);

            if (empty($controllerActionNamesArray)) {
              throw new \Uc\Core\Exception('Route {' . $routeAction . '} is not valid. Can not detect Controller and Action');
            }
            $this->controllerRoute = $controllerActionNamesArray[0];
            $this->actionRoute = $controllerActionNamesArray[1];
            $this->route = $this->controllerRoute . '/' . $this->actionRoute;

            $this->urlIsParsed = true;
            return true;
          }
        }
      } else {
        throw new \Uc\Core\Exception('Url rules is empty');
      }
      return false;
    }

    protected function parseLang() {
      $url = $_SERVER['REQUEST_URI'];

      if (!isset(\Uc::app()->language)) {
        if (!empty($this->baseUrl)) {
          $this->baseUrl = rtrim($this->baseUrl, '/');
          $this->requestUrl = preg_replace('!^' . $this->baseUrl . '!', '', $url);
        } else {
          $this->requestUrl = $url;
        }
      } else {
        $uri = $this->protocol . '://' . $this->hostName . $url;

        foreach (\Uc::app()->language->getLanguages() as $key => $value) {
          $find = false;
          foreach (\Uc::app()->language->getLanguages() as $k => $v) {
            $s = '!^' . $this->protocol . '://' . $v . '/!';
            if (preg_match('!^' . $value . '!', $v) && (strlen($v) > strlen($value))) {
              if (preg_match($s, $uri)) {
                \Uc::app()->language->setCurrent($k);
                $find = true;
                break;
              }
            }
          }

          if ($find) {
            break;
          }

          $search = '!^' . $this->protocol . '://' . $value . '/!';
          if (preg_match($search, $uri)) {
            \Uc::app()->language->setCurrent($key);
            break;
          }
        }

        $this->requestUrl = preg_replace('!^' . $this->protocol . '://' . \Uc::app()->language->getCurrentLangValue() . '!', '', $uri);
      }

      if (empty($this->requestUrl)) {
        $this->requestUrl = '/';
      }
    }

    /**
     * @param $lang
     * @return string
     * @throws Exception
     */
    protected function getRequestLangUrl($lang) {
      if (!\Uc::app()->language->exist($lang)) {
        throw new \Uc\Core\Exception("Current language is not exist in class " . get_class($this));
      }
      return $this->protocol . '://' . \Uc::app()->language->getLangValue($lang);
    }

    /**
     * @param $route
     * @return array
     */
    public function getControllerActionFromRoute($route) {
      preg_match('!^(?<controller>.*)/(?<action>[^/]+)$!', $route, $controllerActionNames);
      if (empty($controllerActionNames)) {
        return null;
      } else {
        return array(
          $controllerActionNames['controller'],
          $controllerActionNames['action']
        );
      }
    }

    /*
     *
     * @return type
     */
    /**
     * @return string
     */
    public function getActionRoute() {
      $this->parseUrl();
      return $this->actionRoute;
    }

    /**
     *
     * @return array
     */
    public function getParams() {
      $this->parseUrl();
      return $this->params;
    }

    /**
     * @return string
     */
    public function getBaseUrl() {
      return $this->baseUrl;
    }


    /**
     * @return string
     */
    public function getAbsoluteRequestUrl() {
      if (isset(\Uc::app()->language)) {
        return $this->getRequestLangUrl(\Uc::app()->language->getCurrent()) . $this->requestUrl;
      }
      return $this->getUrl() . $this->requestUrl;
    }

    /**
     * @param $lang
     * @return string
     * @throws Exception
     */
    public function getAbsoluteRequestUrlByLang($lang) {
      if (isset(\Uc::app()->language)) {
        if (!\Uc::app()->language->exist($lang)) {
          throw new \Uc\Core\Exception("Current language is not exist");
        }
        return $this->getRequestLangUrl($lang) . $this->requestUrl;
      }

      return $this->getUrl() . $this->requestUrl;
    }

    /**
     * Absolute url to index
     *
     * @author  Ivan Scherbak <dev@funivan.com> 8/13/12
     * @return string
     */
    public function getUrl() {
      return $this->protocol . '://' . $this->hostName . $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getRoute() {
      return $this->route;
    }

    /**
     * @param $route
     * @param array $params
     * @param null $code
     */
    public function redirect($route, $params = array(), $code = null) {
      $url = $this->create($route, $params);
      header('Location: ' . $url);
//    @todo show response code
      die();
    }

    /**
     * Create url from route
     * @todo #phpstorm route is reference to action method in controller
     * @param $route
     * @param array $params
     * @param null $lang
     * @return string
     */
    public function create($route, $params = array(), $lang = null) {
      $url = $route;
      if (empty($url) or $url != '/') {

        foreach ($this->rules as $routeRegex => $routeAction) {
          # prepare regexp Url
          $regex = '!^' . $routeAction . '$!';
          $regex = preg_replace('!<([^:]+)>!U', '<$1:.*>', $regex);
          $regex = preg_replace('!<([^:]+):([^>]+)>!U', '(?P<$1>$2)', $regex);
          if (preg_match($regex, $route, $match)) {
            $url = $routeRegex;

            if (strpos($routeRegex, '<') !== false) {
              foreach ($match as $key => $value) {
                $url = preg_replace('!<' . $key . '(:[^>]+|)>!U', trim($value, '/'), $url);
              }
            }
            break;
          }
        }

        if (!empty($params)) {

          foreach ($params as $k => $v) {
            $url = preg_replace('!<' . $k . '(:[^>]+|)>!', $v, $url, -1, $count);
            if ($count) {
              unset($params[$k]);
            }
          }

          if (!empty($params)) {
            $url .= '?' . http_build_query($params);
          }
        }
      }

      if (isset(\Uc::app()->language)) {
        return $this->getRequestLangUrl(empty($lang) ? \Uc::app()->language->getCurrent() : $lang) . $url;
      }

      return $this->getUrl() . $url;
    }

  }