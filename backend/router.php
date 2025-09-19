<?php
// router.php - Roteador simples para APIs

class Router {
    private $routes = [];
    
    public function __construct() {
        // Incluir todas as classes necessárias
        $this->loadClasses();
    }
    
    private function loadClasses() {
        require_once 'config/database.php';
        require_once 'classes/Database.php';
        require_once 'classes/User.php';
        require_once 'classes/Item.php';
        require_once 'classes/Auth.php';
        require_once 'repositories/UserRepository.php';
        require_once 'repositories/ItemRepository.php';
        require_once 'controllers/AuthController.php';
        require_once 'controllers/UserController.php';
        require_once 'controllers/ItemController.php';
        require_once 'controllers/AdminController.php';
        require_once 'includes/functions.php';
        require_once 'includes/session.php';
    }
    
    public function addRoute($method, $path, $controller, $action) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove prefixo se houver
        $path = str_replace('/ecoswap/api', '', $path);
        
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $method, $path)) {
                $this->executeRoute($route, $path);
                return;
            }
        }
        
        // Rota não encontrada
        http_response_code(404);
        jsonResponse(false, 'Rota não encontrada');
    }
    
    private function matchRoute($route, $method, $path) {
        if ($route['method'] !== $method) {
            return false;
        }
        
        $routePath = $route['path'];
        
        // Verificar rotas exatas
        if ($routePath === $path) {
            return true;
        }
        
        // Verificar rotas com parâmetros
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        return preg_match("#^$routePattern$#", $path);
    }
    
    private function executeRoute($route, $path) {
        try {
            $controllerClass = $route['controller'];
            $action = $route['action'];
            
            $controller = new $controllerClass();
            
            // Extrair parâmetros da URL se houver
            $params = $this->extractParams($route['path'], $path);
            
            if (!empty($params)) {
                call_user_func_array([$controller, $action], $params);
            } else {
                $controller->$action();
            }
            
        } catch (Exception $e) {
            logError("Erro ao executar rota: " . $e->getMessage());
            http_response_code(500);
            jsonResponse(false, 'Erro interno do servidor');
        }
    }
    
    private function extractParams($routePath, $actualPath) {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        
        if (preg_match("#^$routePattern$#", $actualPath, $matches)) {
            array_shift($matches); // Remove o match completo
            return $matches;
        }
        
        return [];
    }
}

?>