<?php
declare(strict_types=1);

function render(string $view, array $data = []): void {
  extract($data, EXTR_SKIP);
  include __DIR__ . "/../views/{$view}.php";
}

function csrf_token(): string {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function csrf_field(): string {
  return '<input type="hidden" name="csrf" value="'.htmlspecialchars(csrf_token()).'">';
}

function csrf_verify(string $token): bool {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  return hash_equals($_SESSION['csrf'] ?? '', $token);
}
