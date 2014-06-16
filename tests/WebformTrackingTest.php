<?php

class WebformTrackingTest extends DrupalSeleniumTestCase {
    public $temporary_admin_pass = 'DrushDlDrupal';


    public function testCreateWebform()
    {
        $this->login();
        $this->url('node/add/webform');
        $this->assertContains('Create Webform', $this->title());
        $this->byName('title')->value('Test Form');
        $this->clickOnElement('edit-submit');

        // add a new component to the new webform.
        $this->byName('add[name]')->value('Test textfield');
        $this->clickOnElement('edit-add-add');
        // keep default values for the component.
        $this->clickOnElement('edit-actions-submit');
    }

    public function testFillOutWebform() {
        $this->url('node/' . $this->getLastWebformNid());
        $this->assertContains('Test Form', $this->title());

        $this->byName('submitted[test_textfield]')->value('Test entry');
        $this->clickOnElement('edit-submit');
    }

    public function testAccessAnonymizedData() {
        $last_nid = $this->getLastWebformNid();
        $this->login();
        $form_url = $GLOBALS['base_url'] . '/node/' . $last_nid;
        $data = array(
            'User' => 'XXXXX XXXXX',
            'IP Address' => 'XXX.XXX.XXX.XXX',
            'Referer' => $form_url,
            'External referer' => '',
            'Form URL' => $form_url,
            'Tags' => '',
            'Entry URL' => $form_url,
            'Source' => '',
            'Channel' => '',
            'Version' => '',
            'Other' => '',
            'Country' => '',
            'Test textfield' => 'Test entry',
        );
        $this->checkSubmissionTable($last_nid, $data);
  }

    public function testAccessFullData() {
        $last_nid = $this->getLastWebformNid();
        $this->login();
        $form_url = $GLOBALS['base_url'] . '/node/' . $last_nid;
        $data = array(
            'User' => 'Anonymous (not verified)',
            'IP Address' => '127.0.0.1',
            'Referer' => $form_url,
            'External referer' => '',
            'Form URL' => $form_url,
            'Tags' => '',
            'Entry URL' => $form_url,
            'Source' => '',
            'Channel' => '',
            'Version' => '',
            'Other' => '',
            'Country' => '',
            'Test textfield' => 'Test entry',
        );
        $this->checkSubmissionTable($last_nid, $data);
    }

    private function checkSubmissionTable($nid, $data) {
        $this->url('node/' . $last_nid . '/webform-results/table');
        $table = $this->byCssSelector('.sticky-table');

        $submission = $this->tableToArrays($table)[0];
        foreach ($data as $key => $value) {
            if (empty($value)) {
                $this->assertEmpty($submission[$key]);
            }
            else {
                $this->assertContains($value, $submission[$key]);
            }
        }
    }

    private function tableToArrays($table) {
        $headers = array();
        foreach ($this->multipleByCssSelector($table, 'thead tr th') as $th) {
            $headers[] = $th->text();
        }

        $rows = array();
        foreach ($this->multipleByCssSelector($table, 'tbody tr') as $tr) {
            $row = array();
            $tds = $this->multipleByCssSelector($tr, 'td');
            foreach (array_values($tds) as $i => $td) {
                $row[$headers[$i]] = $td->text();
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function multipleByCssSelector($selector) {
        $root = &$this;
        $args = func_get_args();
        if (count($args) > 1) {
            $root = $args[0];
            $selector = $args[1];
        }
        return $root->elements($root->using('css selector')->value($selector));
    }

    private function getLastWebformNid() {
        $query = db_select('webform', 'w');
        $query->join('node', 'n', 'w.nid = n.nid');
        $query->fields('w', array('nid'))
            ->orderBy('created', 'DESC');
        return $query
            ->execute()
            ->fetchColumn();
    }
}
