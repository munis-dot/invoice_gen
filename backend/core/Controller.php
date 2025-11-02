<?php
require_once __DIR__ . '/Response.php';

class Controller {
    protected function json($data, int $status = 200): void {
        Response::json($data, $status);
    }

    protected function input(): array {
        return json_decode(file_get_contents("php://input"), true) ?? [];
    }
}
