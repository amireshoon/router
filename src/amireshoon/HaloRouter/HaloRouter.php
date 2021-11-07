<?php

namespace Halo;

use Halo\Request;

class Router extends \Bramus\Router\Router {
    
    public function invoke($fn, $params = array())
    {

        if (is_callable($fn)) {
            
            $request = new Request(
                $this->getRequestMethod(),
                $this->getCurrentUri(),
                $this->getRequestHeaders(),
                $this->getRequestBody()
            );

            $params[] = $request;

            call_user_func_array($fn, $params);
        }

        // If not, check the existence of special parameters
        elseif (stripos($fn, '@') !== false) {
            // Explode segments of given route
            list($controller, $method) = explode('@', $fn);

            // Adjust controller class if namespace has been set
            if ($this->getNamespace() !== '') {
                $controller = $this->getNamespace() . '\\' . $controller;
            }

            try {
                $reflectedMethod = new \ReflectionMethod($controller, $method);
                // Make sure it's callable
                if ($reflectedMethod->isPublic() && (!$reflectedMethod->isAbstract())) {
                    if ($reflectedMethod->isStatic()) {
                        forward_static_call_array(array($controller, $method), $params);
                    } else {
                        // Make sure we have an instance, because a non-static method must not be called statically
                        if (\is_string($controller)) {
                            $controller = new $controller();
                        }
                        call_user_func_array(array($controller, $method), $params);
                    }
                }
            } catch (\ReflectionException $reflectionException) {
                // The controller class is not available or the class does not have the method $method
            }
        }
    }

    /**
     * Redirect current route to another route
     * 
     * @since   1.0
     * @param   string  route patternt
     * @param   int     redirect code, default 302
     */
    public function redirect( $path, $code = 302 ) {
        header( "Location: {$path}", true, $code );
        exit;
    }
    
    /**
     * Shorthand for a route accessed using any method.
     * Same functionality as all function
     * 
     * @param string          $pattern A route pattern such as /about/system
     * @param object|callable $fn      The handling function to be executed
     */
    public function any($pattern, $fn)
    {
        $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn);
    }
    
    /**
     * Get request body
     * 
     * @since   2.0
     * @return  mixed
     */
    public function getRequestBody() {
        if ( ! empty( $_POST) ) {
            return $_POST;
        }else {
            return file_get_contents('php://input');
        }
    }
}
