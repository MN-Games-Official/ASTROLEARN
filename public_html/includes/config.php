<?php
/**
 * ASTROLEARN – Application Configuration
 *
 * Copy this file to config.local.php and fill in real credentials.
 * config.local.php is git-ignored so secrets never reach the repo.
 */

// ---------- Environment ----------
define('APP_NAME',  'AstroLearn');
define('APP_ENV',   getenv('APP_ENV')   ?: 'development'); // development | production
define('APP_DEBUG', APP_ENV === 'development');
define('APP_URL',   getenv('APP_URL')   ?: 'http://localhost');

// ---------- Database ----------
define('DB_HOST',   getenv('DB_HOST')   ?: '127.0.0.1');
define('DB_PORT',   getenv('DB_PORT')   ?: '3306');
define('DB_NAME',   getenv('DB_NAME')   ?: 'astrolearn');
define('DB_USER',   getenv('DB_USER')   ?: 'root');
define('DB_PASS',   getenv('DB_PASS')   ?: '');

// ---------- AI Provider ----------
define('AI_PROVIDER',   'abacus');
define('AI_API_URL',    getenv('AI_API_URL')  ?: 'https://routellm.abacus.ai/v1/chat/completions');
define('AI_API_KEY',    getenv('AI_API_KEY')  ?: '');
define('AI_MODEL',      getenv('AI_MODEL')    ?: 'gpt-4');
define('AI_MAX_TOKENS', 1024);

// ---------- Sessions ----------
define('SESSION_LIFETIME', 7200); // 2 hours

// ---------- Security ----------
define('CSRF_TOKEN_NAME', 'csrf_token');
define('BCRYPT_COST', 12);

// ---------- File paths ----------
define('STORAGE_PATH',  __DIR__ . '/../storage');
define('UPLOAD_PATH',   STORAGE_PATH . '/uploads');
define('EXPORT_PATH',   STORAGE_PATH . '/exports');
define('TEMP_PATH',     STORAGE_PATH . '/temp');

// ---------- Load local overrides if present ----------
$localConfig = __DIR__ . '/config.local.php';
if (file_exists($localConfig)) {
    require_once $localConfig;
}
