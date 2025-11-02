<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/JwtHelper.php';

class AuthController extends Controller {

    public function login(): void {
        try {
            // 🔹 Safely decode incoming JSON
            $data = $this->input();
            $email = trim($data['email'] ?? '');
            $password = trim($data['password'] ?? '');

            // 🔹 Validate input
            if (empty($email) || empty($password)) {
                $this->json(['success' => false, 'message' => 'Email and password are required'], 400);
                return;
            }

            // 🔹 Check if user exists
            $user = User::findByEmail($email);
            if (!$user) {
                $this->json(['success' => false, 'message' => 'Invalid email or password'], 401);
                return;
            }

            // 🔹 Verify password
            if (!password_verify($password, $user['password'])) {
                $this->json(['success' => false, 'message' => 'Invalid email or password'], 401);
                return;
            }

            // 🔹 Generate JWT Token
            $token = JwtHelper::encode([
                'id'    => $user['id'],
                'email' => $user['email'],
                'role'  => $user['role'] ?? 'user',
                'iat'   => time()
            ]);

            // 🔹 Respond with success + token + user info
            $this->json([
                'success' => true,
                'message' => 'Login successful',
                'token'   => $token,
                'user'    => [
                    'id'    => $user['id'],
                    'name'  => $user['name'] ?? '',
                    'email' => $user['email'],
                    'role'  => $user['role'] ?? 'user'
                ]
            ]);
        } catch (Throwable $e) {
            // 🔹 Handle any unexpected backend error
            $this->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
