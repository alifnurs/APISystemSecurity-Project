<?php

require_once 'config.php';
require_once 'Database.php';
require_once 'JWTHandler.php';
require_once 'RateLimiter.php';
require_once 'AuthMiddleware.php';
require_once 'UserController.php';

header('Content-Type: application/json');

$db = new Database($dbConfig);
$userController = new UserController($db);
$rateLimiter = new RateLimiter();

$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];


$base_path = '/projectakhir'; 
$route = str_replace($base_path, '', $request_uri);


$route = strtok($route, '?');

$userId = $_SERVER['REMOTE_ADDR'] ?? 'unknown';


if (!$rateLimiter->checkLimit($userId)) {
    http_response_code(429); 
    echo json_encode(['error' => 'Too many requests. Try again later.']);
    exit;
}

$response = [];


error_log("Method: " . $method);
error_log("Route: " . $route);

switch ("$method $route") {
    case 'GET /':
    case 'GET /index.php':
    case 'GET /index.php/':
        $response = [
            'status' => 'success',
            'message' => 'Welcome to Secure API',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /register' => 'Register new user',
                'POST /login' => 'Login user',
                'GET /user/profile' => 'Get user profile (requires authentication)',
                'GET /admin/users' => 'Get users list (requires admin role)'
            ]
        ];
    break;
    case 'POST /index.php/register':
    case 'POST /register':
        
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        
        $data = [];
        if ($contentType === 'application/json') {
           
            
            $data = json_decode(file_get_contents('php://input'), true);
        } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false || 
                  strpos($contentType, 'multipart/form-data') !== false) {
            
           
            $data = [
                'email' => $_POST['email'] ?? null,
                'password' => $_POST['password'] ?? null,
            ];
        } else {
            echo "Unsupported Content-Type";
            exit;
        }
        
        
   
                
        $response = $userController->register($data);
    break;
    case 'POST /index.php/login':
    case 'POST /login':
          
          $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        
          $data = [];
          if ($contentType === 'application/json') {
             
              
              $data = json_decode(file_get_contents('php://input'), true);
          } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false || 
                    strpos($contentType, 'multipart/form-data') !== false) {
              
             
              $data = [
                  'email' => $_POST['email'] ?? null,
                  'password' => $_POST['password'] ?? null,
              ];
          } else {
              echo "Unsupported Content-Type";
              exit;
          }
       
        $response = $userController->login($data);
        break;
        
    case 'GET /index.php/user/profile':
    case 'GET /user/profile':
        $payload = AuthMiddleware::authenticate();
        if (isset($payload['error'])) {
            $response = $payload;
        } else {
            $response = $userController->getProtectedData($payload['user_id']);
        }
        break;
        
    case 'GET /index.php/admin/users':
    case 'GET /admin/users':
        $payload = AuthMiddleware::checkRole('admin');
        if (isset($payload['error'])) {
            $response = $payload;
        } else {
            $response = ['message' => 'Admin access granted'];
        }
        break;
        
    default:
        http_response_code(404);
        $response = ['error' => 'Route not found'];
}

echo json_encode($response);