<?php

namespace system\middlewares;

class RequestHandler implements MiddlewareInterface {

    public function handle($requestContext, &$response ){
        ob_start();
        $request_uri = $_SERVER["REQUEST_URI"];
        if($requestContext->routing->resolveRoute($request_uri, $route) === false){
            $className = "system\controller\NotFoundController";
            $methodName = "index";
            $arguments = array();
        }else{
            $className = CONTROLLER_LOCATION . $route->controller . 'Controller';
            $methodName = $route->action;
        }

        $class = new \ReflectionClass($className);
        $parameters = $class->getConstructor()->getParameters();

        $injectable_objects = array();
        foreach ($parameters as $parameter) {
            array_push($injectable_objects, array(
                $parameter->getPosition(),
                $parameter->getClass()
            ));
        }

        ksort($injectable_objects);

        $args = array();
        foreach ($injectable_objects as $injectable_object) {
            array_push($args, $requestContext->container->get($injectable_object[1]));
        }

        if (count($args) == 0)
            $object = new $className();
        else {
            $object = $class->newInstanceArgs($args);
        }
        
        call_user_func_array(array(
            $object,
            $methodName
        ),  $arguments ?? $route->args);

        $out = ob_get_clean();
        $response = $out;    
    }
}