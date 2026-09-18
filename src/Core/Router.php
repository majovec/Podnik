<?php
namespace App\Core;
final class Router {
    private array $routes=[];
    public function get(string $p, callable $h):self{$this->routes[]=['GET',$p,$h];return $this;}
    public function post(string $p, callable $h):self{$this->routes[]=['POST',$p,$h];return $this;}
    public function put(string $p, callable $h):self{$this->routes[]=['PUT',$p,$h];return $this;}
    public function delete(string $p, callable $h):self{$this->routes[]=['DELETE',$p,$h];return $this;}
    public function dispatch(string $method,string $uri): void {
        $path=parse_url($uri,PHP_URL_PATH) ?: '/';
        foreach($this->routes as [$m,$pattern,$handler]){
            if($m!==strtoupper($method)) continue;
            $re=preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#','(?P<$1>[^/]+)',$pattern);
            if(preg_match('#^'.$re.'$#',$path,$mm)){
                $args=[]; foreach($mm as $k=>$v) if(!is_int($k)) $args[]=$v;
                $handler(...$args); return;
            }
        }
        Response::abort(404,'Stránka nebyla nalezena.');
    }
}
