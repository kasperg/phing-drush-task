<?php

/**
 * @file
 * A Phing task to run Drush commands
 */

require_once "phing/Task.php";

class DrushParam {
  private $value;

  public function addText($str) { $this->value = $str;}
  public function getValue() { return $this->value;}
}

class DrushOption {
  private $name;
  private $value;

  public function setName($str) { $this->name = $str;}
  public function getName() { return $this->name;}

  public function addText($str) { $this->value = $str;}
  public function getValue() { return $this->value;}
}

class DrushTask extends Task {

    /**
     * The message passed in the buildfile.
     */
    private $command = NULL;
    private $bin = NULL;
    private $uri = NULL;
    private $root = NULL;
    private $assume = NULL;
    private $simulate = FALSE;
    private $pipe_property = NULL;
    private $options = array();
    private $params = array();
    private $pipe_glue = "\n";
    private $verbose = FALSE;

    /**
     * The Drush command to run.
     */
    public function setCommand($str) {
      $this->command = $str;
    }

    /**
     * Path the Drush executable.
     */
    public function setBin($str) {
      $this->bin = $str;
    }

    /**
     * Drupal root directory to use.
     */
    public function setRoot($str) {
      $this->root = $str;
    }

    /**
     * URI of the Drupal to use.
     */
    public function setUri($str) {
      $this->uri = $str;
    }

    /**
     * Assume 'yes' or 'no' to all prompts.
     */
    public function setAssume($var) {
      if (is_string($var)) {
        $this->assume = ($var === 'yes');
      }
      else {
        $this->assume = !!$var;
      }
    }

    /**
     * Simulate all relevant actions.
     */
    public function setSimulate($var) {
      if (is_string($var)) {
        $var = strtolower($var);
        $this->simulate = ($var === 'yes' || $var === 'true');
      }
      else {
        $this->simulate = !!$var;
      }
    }

    /**
     * The name of a Phing property to 'pipe' the Drush command's output to.
     */
    public function setPipe($property) {
      $this->pipe_property = (string)$property;
    }

    /**
     * The 'glue' characters used between each line of the piped output.
     */
    public function setPipeGlue($glue) {
      $this->pipe_glue = (string)$glue;
    }

    /**
     * Parameters for the Drush command.
     */
    public function createParam() {
      $o = new DrushParam();
      $this->params[] = $o;
      return $o;
    }

    /**
     * Options for the Drush command.
     */
    public function createOption() {
      $o = new DrushOption();
      $this->options[] = $o;
      return $o;
    }

    /**
     * Display extra information about the command.
     */
    public function setVerbose($var) {
      if (is_string($var)) {
        $this->verbose = ($var === 'yes');
      }
      else {
        $this->verbose = !!$var;
      }
    }

    /**
     * Initialize the task.
     */
    public function init() {
      // Get default root, uri and binaru from project.
      $this->root = $this->getProject()->getProperty('drush.root');
      $this->uri = $this->getProject()->getProperty('drush.uri');
      $this->bin = $this->getProject()->getProperty('drush.bin');
    }

    /**
     * The main entry point method.
     */
    public function main() {
      // Build command line
      if (!empty($this->bin)) {
        $command = $this->bin;
      }
      else {
        $command = "drush";
      }
      $command .= " --nocolor";
      if (!empty($this->root)) {
        $command .= " --root=$this->root";
      }
      if (!empty($this->uri)) {
        $command .= " --uri=$this->uri";
      }
      if (is_bool($this->assume)) {
        $command .= ' --' . ($this->assume ? 'yes' : 'no');
      }
      if ($this->simulate) {
        $command .= ' --simulate';
      }
      if (!empty($this->pipe_property)) {
        $command .= ' --pipe';
      }

      if ($this->verbose) {
        $command .= ' --verbose';
      }
      $command .= ' ';
      $command .= $this->command;
      foreach ($this->options as $option) {
        $command .= ' --';
        $command .= $option->getName();
        $v = $option->getValue();
        if (!empty($v)) {
          $command .= '=';
          $command .= $option->getValue();
        }
      }
      foreach ($this->params as $param) {
        $command .= ' ';
        $command .= $param->getValue();
      }
      $command = trim($command);

      // Execute Drush
      $this->log("Executing '$command'...");
      $output = array();
      exec($command, $output, $return);
      // Collect Drush output for display through Phing's log
      foreach ($output as $line) {
        $this->log($line);
      }
      // Set value of the 'pipe' property
      if (!empty($this->pipe_property)) {
        $this->getProject()->setProperty($this->pipe_property, implode($this->pipe_glue, $output));
      }
      // Build fail
      if ($return != 0 ) {
        throw new BuildException("Drush exited with code $return");
      }
      return $return != 0;
    }
}
