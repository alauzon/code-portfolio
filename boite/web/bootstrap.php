<?php
use Boite\Logger;
use Boite\SessionManager;
use Boite\TemplateRenderer;
use Boite\CSRFProtector;

// Declare global variables.
global $db;
global $logger;
global $templateRenderer;
global $sessionManager;
global $csrfProtector;
global $config;

// Define paths.
define('ROOT_DIR', dirname(__FILE__));
const SRC_DIR = ROOT_DIR . '/src';
const CSS_DIR = ROOT_DIR . '/css';
const LOGS_DIR = ROOT_DIR . '/logs';
const TEMPLATES_DIR = ROOT_DIR . '/templates';

// Autoload pour les classes
/*spl_autoload_register(function ($class) {
  require_once SRC_DIR . '/' . $class . '.php';
});*/
require_once('../vendor/autoload.php');

// Inclure des fichiers de configuration si nécessaire
require_once 'config.php';

// Initializing main components
try {
  $db = new \Boite\Database();
  $db = $db->getConnection();
} catch (PDOException $e) {
  // Manage the db connection error
  die("Database connection error: " . $e->getMessage());
}

// Initialize services
$logger = new Logger(LOGS_DIR . '/boite.logs');
$templateRenderer = new TemplateRenderer(TEMPLATES_DIR);
$sessionManager = new SessionManager();
$csrfProtector = new CSRFProtector();
