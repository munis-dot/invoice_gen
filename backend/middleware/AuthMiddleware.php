<?php
require_once __DIR__ . '/../helpers/JwtHelper.php';
require_once __DIR__ . '/../core/Response.php';

class AuthMiddleware {
    public static function handle(bool $requireAdmin = false): array {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            Response::json(['error' => 'Authorization header missing'], 401);
            exit;
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        $user = JwtHelper::decode($token);
        if (!$user) {
            Response::json(['error' => 'Invalid or expired token'], 401);
            exit;
        }

        if ($requireAdmin && ($user['role'] ?? '') !== 'admin') {
            Response::json(['error' => 'Access denied'], 403);
            exit;
        }

        return $user;
    }
}
