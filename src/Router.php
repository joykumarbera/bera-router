<?php

namespace bera\router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Joy Kumar Bera<joykumarbera@gmail.com>
 */
class Router
{
    /**
     * @var array $routes
     */
    private $routes;

    /**
     * @var string $controller_namespace
     */
    private $controller_namespace;

    /**
     * @var string $middleware_namespace
     */
    private $middleware_namespace;

    public function __construct(string $controller_namespace = null, string $middleware_namespace = null)
    {
        $this->routes = [];
        $this->controller_namespace = $controller_namespace ?? '\\app\\controllers\\';
        $this->middleware_namespace = $middleware_namespace ?? '\\app\\middlewares\\';
    }

    /**
     * Add a get endpoint
     * 
     * @param string $endpoint
     * @param callback $callback
     */
    public function get($endpoint, $callback, $middlewares = [])
    {
        $this->addRequset('GET', $endpoint, $callback, $middlewares);
    }

    /**
     * Add a post endpoint
     * 
     * @param string $endpoint
     * @param callback $callback
     */
    public function post($endpoint, $callback, $middlewares = [])
    {
        $this->addRequset('POST', $endpoint, $callback, $middlewares);
    }

    /**
     * Add request to the router
     * 
     * @param string $type
     * @param string $endpoint
     * @param callback $callback
     * @param array $middlewares
     */
    private function addRequset($type, $endpoint, $callback, $middlewares)
    {
        $route_handler = [];
        $route_handler['callback'] = $callback;
        $route_handler['type'] = $type;

        if(array_key_exists('before', $middlewares)) {
            if( !empty($middlewares['before']) ) {
                $route_handler['before_middlewares'] = $middlewares['before'];
            }
        }

        if(array_key_exists('after', $middlewares)) {
            if( !empty($middlewares['after']) ) {
                $route_handler['after_middlewares'] = $middlewares['after'];
            }
        }
        
        $replacement = '([\w\-_]+)';
        $url_params = [];
        $decorated_string = preg_replace_callback(
            '/\{(.*?)\}/',
            function ($matches) use (&$url_params, $replacement) {
                $url_params[] = $matches[1];
                return $replacement;
            },
            $endpoint
        );

        $final_endpoint = '#^' . $decorated_string . '$#';
        $route_handler['params'] = $url_params;  
        
        $this->routes[$final_endpoint] = $route_handler;
    }

    /**
     * Get available routes
     * 
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
    
    private function getRequestMethodAndRoute()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $request_uri = $_SERVER['REQUEST_URI'];

        $endpoint = '/';

        if(strpos($request_uri, '?') !== false) {
            $endpoint = $request_uri;
        } else {
            $endpoint = explode('?', $request_uri)[0];
        }

        return [$method, $endpoint];
    }

    /**
     * Start the router
     */
    public function dispatch()
    {
        list($method, $endpoint) = $this->getRequestMethodAndRoute();

        $url_params_values = [];
        $callback = '';
        foreach($this->routes as $route => $route_info) {
            if(preg_match($route, $endpoint, $matches) && $route_info['type'] == $method) {
                
                foreach($route_info['params'] as $param) {
                    if( array_key_exists($param, $matches) ) {
                        $url_params_values[$param] = $matches[$param];
                    }
                }

                $callback = $route_info['callback'];
                break;
            }
        }

        if($callback == '') {
            http_response_code(404);
            die("No route found");
        }

       
        list($className, $actionName) = explode('@', $callback);


        $class = $this->controller_namespace . $className;

        $classInstance = new $class();

        $request = Request::createFromGlobals();
        $response = new Response(
            'Content',
            Response::HTTP_OK,
            ['content-type' => 'text/html']
        );

        $before_middleware_status = true;
        if(isset($this->routes[$endpoint]['before_middlewares'])) {
            $before_middleware_status = $this->applyMiddlewares(
                'before',
                $endpoint,
                $request,
                $response
            );
        }

        if($before_middleware_status) {
            // hit the acctual controller
            if(!empty($url_params_values)) {
                call_user_func_array([$classInstance, $actionName], array_values($url_params_values));
            } else {
                call_user_func_array([$classInstance, $actionName], [
                    $request, $response
                ]);
            }
        }
        
        if(isset($this->routes[$endpoint]['after_middlewares'])) {
            $this->applyMiddlewares(
                'after',
                $endpoint,
                $request,
                $response
            );
        }
    }

    /**
     * Apply middleware
     * 
     * @param string $type
     * @param string $endpoint
     * @param Request $request
     * @param Response $response
     * 
     * @return bool
     */
    private function applyMiddlewares($type, $endpoint, $request, $response)
    {
        $middleware_type = $type . '_middlewares';

        foreach($this->routes[$endpoint][$middleware_type] as $middleware) {

            $middlewareClass = $this->middleware_namespace . $middleware;
            $middlewareInstance = new $middlewareClass();

            if(!method_exists($middlewareInstance, 'handle')) {
                throw new \Exception(
                    sprintf("no handle method found in %s", get_class($middlewareInstance))
                );
            }

            $middleware_status = call_user_func_array([$middlewareInstance, 'handle'], [
                $request, $response
            ]);

            if($middleware_status !== true) {
                return false;
                break;
            }
        }

        return true;
    }
}