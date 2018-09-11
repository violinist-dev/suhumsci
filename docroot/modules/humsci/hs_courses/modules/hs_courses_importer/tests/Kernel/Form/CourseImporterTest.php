<?php

namespace Drupal\Tests\hs_courses_importer\Kernel\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class HsCoursesImporterFormTest.
 *
 * @coversDefaultClass \Drupal\hs_courses_importer\Form\CourseImporter
 * @group hs_courses_importer
 * @group coverage
 */
class CourseImporterTest extends KernelTestBase implements ServiceModifierInterface {

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Namespace of the form.
   *
   * @var string
   */
  protected $formClass = '\Drupal\hs_courses_importer\Form\CourseImporter';

  /**
   * A valid testable URL.
   *
   * @var string
   */
  protected $validUrl = 'http://explorecourses.stanford.edu/search?view=xml&q=abcdefg';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'hs_courses_importer',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->formBuilder = \Drupal::formBuilder();
  }

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/project/drupal/issues/2571475#comment-11938008
   */
  public function alter(ContainerBuilder $container) {
    $container->removeDefinition('test.http_client.middleware');
  }

  /**
   * Test the form class and its methods.
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::getFormId
   * @covers ::getEditableConfigNames
   * @covers ::buildForm
   * @covers ::validateForm
   * @covers ::validateIsExploreCourses
   * @covers ::validateIsXmlUrl
   * @covers ::submitForm
   */
  public function testForm() {
    $form = $this->formBuilder->getForm($this->formClass);
    $this->assertArrayHasKey('urls', $form);

    $form_state = new FormState();
    $form_state->setValue('urls', 'garbage url');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', 'http://google.com');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', 'http://explorecourses.stanford.edu/search');
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertNotEmpty($form_state->getErrors());
    $form_state->clearErrors();

    $form_state->setValue('urls', $this->validUrl);
    $this->formBuilder->submitForm($this->formClass, $form_state);
    $this->assertEmpty($form_state->getErrors());

    $config_urls = $this->config('hs_courses_importer.importer_settings')
      ->get('urls');
    $this->assertEquals($this->validUrl, reset($config_urls));
  }

}
