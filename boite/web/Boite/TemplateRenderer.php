<?php
namespace Boite;
class TemplateRenderer
{
  private $templatePath;

  public function __construct($templatePath)
  {
    $this->templatePath = $templatePath;
  }

  // Fonction pour rendre un template HTML avec des variables données
  public function render($template, $variables = [])
  {
    extract($variables);

    ob_start();
    include $this->templatePath . '/' . $template;
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
  }
}
