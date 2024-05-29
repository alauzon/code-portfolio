<?php
namespace Boite;

class Logger
{
  private $logFile;

  public function __construct($logFilePath)
  {
    $this->logFile = $logFilePath;
  }

  // Fonction pour écrire un message dans le fichier de log
  public function writeLog($message)
  {
    $date = date('Y-m-d H:i:s');
    $logMsg = "$date: $message\n";

    file_put_contents($this->logFile, $logMsg, FILE_APPEND);
  }

  // Fonctions spécifiques pour différents types de logs (par exemple, erreurs, authentification, etc.)
  public function logError($message)
  {
    $this->writeLog("ERROR: $message");
  }

  public function logInfo($message)
  {
    $this->writeLog("INFO: $message");
  }

  public function logDebug($message)
  {
    global $config;
    if ($config['debug'] ?? false) {
      $this->writeLog("DEBUG: $message");
    }
  }

  // ... Autres méthodes utiles pour la journalisation
}
