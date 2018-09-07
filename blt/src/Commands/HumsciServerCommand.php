<?php

namespace Acquia\Blt\Custom\Commands;

use Acquia\Blt\Robo\Commands\Artifact\AcHooksCommand;

/**
 * Class HumsciServerCommand.
 *
 * @package Acquia\Blt\Custom\Commands
 */
class HumsciServerCommand extends AcHooksCommand {

  protected $apiEndpoint = 'https://cloudapi.acquia.com/v1';

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys
   */
  public function humsciKeys() {
    $this->taskDrush()
      ->drush("rsync --mode=rltDkz @default.prod:/mnt/gfs/swshumsci.prod/nobackup/apikeys/ @self:../keys")
      ->run();
  }

  /**
   * Get encryption keys from acquia.
   *
   * @command humsci:keys:send
   *
   * @param string $env
   *   Acquia environment to send the keys.
   */
  public function humsciKeysSend($env = 'prod') {
    $send = $this->askQuestion('Are you sure you want to copy over existing keys with keys in the "keys" directory? (Y/N)', 'N', TRUE);
    $key_dir = $this->getConfigValue("key-dir.$env");
    if (strtolower($send[0]) == 'y') {
      $this->taskDrush()
        ->drush("rsync @self:../keys/ @default.$env:$key_dir")
        ->run();
    }
  }

  /**
   * @command humsci:add-domain
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciAddDomain() {

    $username = $this->askQuestion('Acquia Username. Usually an email', '', TRUE);
    $password = $this->askHidden('Acquia Password');

    $new_domains = [];
    while ($domain = $this->askQuestion('Domain to add. Leave empty when done')) {
      while (empty($environment = $this->askChoice('Which environment', [
        'dev',
        'test',
        'prod',
      ], ''))) {
        continue;
      }
      $url = "{$this->apiEndpoint}/sites/devcloud:swshumsci/envs/$environment/domains/$domain.json";

      $this->say($url);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
      curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      curl_setopt($ch, CURLOPT_POST, 1);
      $output = curl_exec($ch);
      $info = curl_getinfo($ch);
      curl_close($ch);
      $this->say($output);

      $new_domains[$environment][] = $domain;
    }

    foreach ($new_domains as $environment => $domains) {
      $this->humsciLetsEncryptAdd($environment, ['domains' => $domains]);
    }
  }

  /**
   * @command humsci:letsencrypt:list
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @return array
   *   Existing domains on the cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptList($environment = 'dev') {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $shell_command = "cd ~ && .acme.sh/acme.sh --list --listraw";
    $php_command = "return shell_exec('$shell_command');";
    $results = $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->run();

    $results = $results->getMessage();

    $domain_environment = str_replace('test', 'stage', $environment);
    $matches = preg_grep("/^.*-$domain_environment.*/", explode("\n", $results));
    $cert = reset($matches);
    preg_match_all("/[a-z].*?\.edu/", $cert, $domains);
    $domains = $domains[0];

    return $domains;
  }

  /**
   * @command humsci:letsencrypt:add-domain
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptAdd($environment = 'dev', $options = ['domains' => []]) {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $domains = $this->humsciLetsEncryptList($environment);

    $this->say('Existing domains on the cert:' . PHP_EOL . implode(PHP_EOL, $domains));

    $domains = array_merge($domains, $options['domains']);
    $domains = array_merge($domains, $this->getDomains());

    $primary_domain = array_shift($domains);
    asort($domains);
    $domains = "-d $primary_domain -d " . implode(' -d ', $domains);

    $directory = "/mnt/gfs/swshumsci.$environment/files/";
    $shell_command = "cd ~ && .acme.sh/acme.sh --issue $domains -w $directory";
    $php_command = "return shell_exec('$shell_command');";
    $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->run();
  }

  /**
   * Get new domains to add to Cert.
   *
   * @return array
   *   New domains.
   */
  protected function getDomains() {
    $domains = [];
    while ($new_domain = $this->askQuestion('New Domain? Leave empty to create cert')) {
      if (strpos($new_domain, '.stanford.edu') === FALSE) {
        $this->say('Invalid domain. Must be a stanford domain.');
        continue;
      }
      if (strpos($new_domain, 'http') !== FALSE) {
        $this->say('Invalid domain. Do not include the domain protocol.');
        continue;
      }
      $domains[] = trim($new_domain, ' /\\');
    }
    return $domains;
  }

  /**
   * @command humsci:letsencrypt:get-cert
   *
   * @param string $environment
   *   Which environment to add to cert.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function humsciLetsEncryptGet($environment = 'dev') {
    if (!in_array($environment, ['dev', 'test', 'prod'])) {
      $this->say('invalid environment');
      return;
    }

    $domains = $this->humsciLetsEncryptList($environment);
    $primary_domain = array_shift($domains);

    $file = $this->askChoice('Which file would you like to get?', [
      'Certificate',
      'Private Key',
      'Intermediate Certificates',
    ], 'Certificate');
    switch ($file) {
      case 'Private Key':
        $file = "$primary_domain.key";
        break;
      case 'Intermediate Certificates':
        $file = 'ca.cer';
        break;
      default:
        $file = "$primary_domain.cer";
        break;
    }

    $shell_command = "cd ~ && cat .acme.sh/$primary_domain/$file";
    $php_command = "return shell_exec('$shell_command');";
    $this->taskDrush()
      ->alias($this->getConfigValue('drush.aliases.remote'))
      ->drush('eval')
      ->arg($php_command)
      ->run();
  }

}