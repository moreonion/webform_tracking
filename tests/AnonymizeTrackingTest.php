<?php

namespace Drupal\webform_tracking;

/**
 * Test newsletter subscription activities related to IP addresses.
 */
class AnonymizeTrackingTest extends \DrupalWebTestCase {

  /**
   * Set up.
   */
  public function setUp() {
    $this->oldserver = $_SERVER;
    $this->remote_ip = '127.210.33.7';
    drupal_static_reset('ip_address');
    // @codingStandardsIgnoreLine
    $_SERVER['REMOTE_ADDR'] = $this->remote_ip;

    // Defaults for variable_get.
    $GLOBALS['conf']['webform_tracking_track_ip_address'] = FALSE;
    $GLOBALS['conf']['webform_tracking_significant_ip_address_bits'] = 16;

    parent::setUp();

    db_delete('webform_tracking')->execute();
  }

  /**
   * Tear down.
   */
  public function tearDown() {
    $_SERVER = $this->oldserver;
    drupal_static_reset('ip_address');

    parent::tearDown();

    db_delete('webform_tracking')->execute();
  }

  /**
   * The Extractor writes a record.
   */
  public function testExtractor() {
    $submission = array(
      'nid' => 1,
      'sid' => 1,
    );
    $cookie = Extractor::fromEnv()->saveVars((object) $submission);

    $count = db_select('webform_tracking')
      ->condition('nid', 1)
      ->condition('sid', 1)
      ->countQuery()->execute()->fetchField();
    $this->assertEqual(1, $count);
  }

  /**
   * By default no IP address is stored.
   */
  public function testTrackingDisabledByDefault() {
    $submission = array(
      'nid' => 1,
      'sid' => 1,
    );
    $cookie = Extractor::fromEnv()->saveVars((object) $submission);

    $tracking = db_select('webform_tracking', 't')
      ->fields('t')
      ->condition('nid', 1)
      ->condition('sid', 1)
      ->execute()->fetch();

    // Empty string means disabled.
    $this->assertEqual('', $tracking->ip_address);
  }

  /**
   * Enabled tracking with a bitmask.
   */
  public function testTrackingEnabledWithDefaultMask() {
    $GLOBALS['conf']['webform_tracking_track_ip_address'] = TRUE;

    $submission = array(
      'nid' => 1,
      'sid' => 1,
    );
    $cookie = Extractor::fromEnv()->saveVars((object) $submission);

    $tracking = db_select('webform_tracking', 't')
      ->fields('t')
      ->condition('nid', 1)
      ->condition('sid', 1)
      ->execute()->fetch();

    $this->assertEqual('127.210.0.0', $tracking->ip_address);
  }

  /**
   * Enabled tracking with a specified bitmask.
   */
  public function testTrackingEnabledWithCustomMask() {
    $GLOBALS['conf']['webform_tracking_track_ip_address'] = TRUE;
    $GLOBALS['conf']['webform_tracking_significant_ip_address_bits'] = 6;

    $submission = array(
      'nid' => 1,
      'sid' => 1,
    );
    $cookie = Extractor::fromEnv()->saveVars((object) $submission);

    $tracking = db_select('webform_tracking', 't')
      ->fields('t')
      ->condition('nid', 1)
      ->condition('sid', 1)
      ->execute()->fetch();

    $this->assertEqual('124.0.0.0', $tracking->ip_address);
  }

  /**
   * Track the full IP address.
   */
  public function testTrackingEnabledWithFullAddress() {
    $GLOBALS['conf']['webform_tracking_track_ip_address'] = TRUE;
    $GLOBALS['conf']['webform_tracking_significant_ip_address_bits'] = 32;

    $submission = array(
      'nid' => 1,
      'sid' => 1,
    );
    $cookie = Extractor::fromEnv()->saveVars((object) $submission);

    $tracking = db_select('webform_tracking', 't')
      ->fields('t')
      ->condition('nid', 1)
      ->condition('sid', 1)
      ->execute()->fetch();

    $this->assertEqual('127.210.33.7', $tracking->ip_address);
  }

}
