<?php

namespace Drupal\hs_bugherd;

use Bugherd\Client;
use Drupal\key\Entity\Key;

/**
 * Class BugherdApi.
 *
 * @package Drupal\bugherdapi
 */
class HsBugherd {

  const BUGHERDAPI_ORGANIZATION = 'organization';

  const BUGHERDAPI_USER = 'user';

  const BUGHERDAPI_PROJECT = 'project';

  const BUGHERDAPI_TASK = 'task';

  const BUGHERDAPI_COMMENT = 'comment';

  const BUGHERDAPI_WEBHOOK = 'webhook';

  const BUGHERDAPI_BACKLOG = 'backlog';

  const BUGHERDAPI_TODO = 'todo';

  const BUGHERDAPI_DOING = 'doing';

  const BUGHERDAPI_DONE = 'done';

  const BUGHERDAPI_CLOSED = 'closed';

  /**
   * The Bugherd api key.
   *
   * @var string
   */
  protected $apiKey;

  /**
   * @var \Bugherd\Client
   */
  protected $client;

  /**
   * HsBugherd constructor.
   */
  public function __construct() {
    $this->apiKey = static::getApiKey();
    $this->projectKey = self::getProjectId();
    $this->client = new Client($this->apiKey);
  }

  /**
   * Get the configured Bugherd API key.
   *
   * @return string|null
   *   API key.
   */
  public static function getApiKey() {
    $key_id = \Drupal::configFactory()
      ->get('bugherdapi.settings')
      ->get('api_key');
    /** @var Key $key */
    if ($key_id && $key = Key::load($key_id)) {
      return $key->getKeyValue();
    }
  }

  /**
   * @param string $api_key
   */
  public function setApiKey($api_key) {
    $this->apiKey = $api_key;
    // Rebuild the client
    $this->client = new Client($this->apiKey);
  }

  /**
   * Test if the api connection works.
   *
   * @return bool
   */
  public function connectionSuccessful() {
    $test = $this->getOrganization();
    return !isset($test['error']);
  }

  /**
   * @return array|mixed|null
   */
  public static function getProjectId() {
    return \Drupal::configFactory()
      ->get('bugherdapi.settings')
      ->get('project_id');
  }

  /**
   * @param string $project_id
   */
  public function setProjectId($project_id) {
    $this->projectKey = $project_id;
  }

  /**
   * Get the desired API.
   *
   * @return \Bugherd\Api\AbstractApi
   *   The api.
   */
  protected function getApi($api) {
    return $this->client->api($api);
  }

  /**
   * Get information about the Bugherd Org.
   *
   * @return array
   *   Returned response.
   */
  public function getOrganization() {
    return $this->getApi(self::BUGHERDAPI_ORGANIZATION)->show();
  }

  /**
   * Get a list of all members and guests in the organization.
   *
   * @param bool $members
   *   Get the Members.
   * @param bool $guests
   *   Get the Guests
   *
   * @return array
   *   Returned response.
   */
  public function getAllUsers($members = TRUE, $guests = TRUE) {
    if ($guests && $members) {
      return $this->getApi(self::BUGHERDAPI_USER)->all();
    }
    if ($members) {
      return $this->getApi(self::BUGHERDAPI_USER)->getMembers();
    }
    if ($guests) {
      return $this->getApi(self::BUGHERDAPI_USER)->getGuests();
    }
    return [];
  }

  /**
   * Get list of all members in the organization.
   *
   * @return array
   *   Returned response.
   */
  public function getMembers() {
    return $this->getAllUsers(TRUE, FALSE);
  }

  /**
   * Get list of all guests in the organization.
   *
   * @return array
   *   Returned response.
   */
  public function getGuests() {
    return $this->getAllUsers(FALSE, TRUE);
  }

  /**
   * Get a list of all projects in the organization.
   *
   * @return array
   *   Returned response.
   */
  public function getProjects() {
    return $this->getApi(self::BUGHERDAPI_PROJECT)->listing(FALSE, FALSE);
  }

  /**
   * Get all task for a project.
   *
   * @param integer $project_id
   *   Project id found from getProjects().
   * @param array $params
   *   Array of possible search parameters.
   *
   * @return array
   *   Returned response.
   *
   * https://www.bugherd.com/api_v2#api_task_list
   */
  public function getTasks($project_id = NULL, array $params = []) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->all($project_id ?: $this->projectKey, $params);
  }

  /**
   * Get a specific task
   *
   * @param int $task_id
   *   Bugherd Task ID.
   * @param int|null $project_id
   *   Project ID if the task is in another project.
   *
   * @return array
   *   Task data.
   *
   * @see https://www.bugherd.com/api_v2#api_task_show
   */
  public function getTask($task_id, $project_id = NULL) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->show($project_id ?: $this->projectKey, $task_id);
  }

  /**
   * Update a specific task with the given data.
   *
   * @param int $task_id
   *   Bugherd Task id.
   * @param array $data
   *   Keyed array of update data.
   * @param int|null $project_id
   *   Project ID if the task is in another project.
   *
   * @return mixed
   *   Update response.
   *
   * @see https://www.bugherd.com/api_v2#api_task_update
   */
  public function updateTask($task_id, $data, $project_id = NULL) {
    return $this->getApi(self::BUGHERDAPI_TASK)
      ->update($project_id ?: $this->projectKey, $task_id, $data);
  }

  /**
   * Get all the commments on a particular task.
   *
   * @param integer $task_id
   *   Task id found from getTasks().
   * @param integer $project_id
   *   Project id found from getProjects().
   *
   * @return array
   *   Returned response.
   *
   * @see https://www.bugherd.com/api_v2#api_comment_list
   */
  public function getComments($task_id, $project_id = NULL) {
    return $this->getApi(self::BUGHERDAPI_COMMENT)
      ->all($project_id ?: $this->projectKey, $task_id);
  }

  /**
   * Add a comment to a task.
   *
   * @param int $task_id
   *   Task ID.
   * @param array $comment_data
   *   Keyed array of comment data.
   * @param int|null $project_id
   *   Project ID if different than current.
   *
   * @throws \Exception
   *
   * @see https://www.bugherd.com/api_v2#api_comment_create
   */
  public function addComment($task_id, array $comment_data, $project_id = NULL) {
    if (!isset($comment_data['text'])) {
      throw new \Exception('Text is required to add a comment');
    }
    $this->getApi(self::BUGHERDAPI_COMMENT)
      ->create($project_id ?: $this->projectKey, $task_id, $comment_data);
  }

  /**
   * Get a list of all the hooks in the organization.
   *
   * @return array
   *   Returned response.
   *
   * @see https://www.bugherd.com/api_v2#api_webhook_list
   */
  public function getHooks() {
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->all();
  }

  /**
   * Creaet a bugherd webhook.
   *
   * @param array $params
   *   Keyed array of webhook data.
   *
   * @return array
   *   Returned response.
   *
   * @throws \Exception
   *   Event is required.
   *
   * @see https://www.bugherd.com/api_v2#api_webhook_create
   */
  public function createWebhook(array $params) {
    if (empty($params['event'])) {
      throw new \Exception('Event is required by the API');
    }
    $params += ['target_url' => ''];
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->create($params);
  }

  /**
   * Delete a specific webook.
   *
   * @param $id
   *   Webhook id found from getHooks().
   *
   * @return mixed
   *   Returned response.
   *
   * @see https://www.bugherd.com/api_v2#api_webhook_delete
   */
  public function deleteWebhook($id) {
    return $this->getApi(self::BUGHERDAPI_WEBHOOK)->remove($id);
  }

}