<?php

namespace Drupal\webform_tracking;

use Upal\DrupalUnitTestCase;

/**
 * Test the webform hook implementations.
 */
class WebformTest extends DrupalUnitTestCase {

  /**
   * Test getting the submission data with the patch from #3086038.
   *
   * @link https://www.drupal.org/node/3086038 #3086038 @endlink
   */
  public function testSubmissionDataWith3086038() {
    $submission = (object) ['tracking' => []];
    $submission->tracking['tags'] = ['foo', 'bar', 'baz'];
    $data = webform_tracking_webform_results_download_submission_information_data_row($submission, [], 0, 1);
    $this->assertEqual([
      'webform_tracking_tags' => 'foo, bar, baz',
    ], $data);
  }

  /**
   * Test getting the submission data without the patch from #3086038.
   */
  public function testSubmissionDataWithoutPatch() {
    $submission = (object) ['tracking' => []];
    $submission->tracking['tags'] = ['foo', 'bar', 'baz'];
    $data = webform_tracking_webform_results_download_submission_information_data('webform_tracking_tags', $submission, [], 0, 1);
    $this->assertEqual('foo, bar, baz', $data);
  }

}
