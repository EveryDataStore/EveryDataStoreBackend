<?php

namespace EveryDataStore\Tests\Model\RecordSet;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\RecordSet\Form\FormSection;

class FormSectionTest extends SapphireTest {
    protected static $fixture_file = '../../../fixture/EveryDataStoreTest.yml';
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }
    protected function setUp(): void {
        parent::setUp();
        $me = Member::get()->first();
        $this->logInAs($me);
    }

    /**
     * Simple test for object creation 
     */
    public function testFormSectionCreation() {
        $testObj = $this->objFromFixture(FormSection::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
    }

    /**
     * Testing if the a record will create and if the other relations will be deleted.
     */
    public function testFormSectionDeletion() {
        $testObj = $this->objFromFixture(FormSection::class, 'FirstObj');
        $testObj->delete();
        $this->assertEquals(0, $testObj->Columns()->Count(), 'FormSection Columns count');
    }

    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Slug' => 'MyFormSection',
                'FormID' => 1
            ]
        ];
        return $data[$k] ? $data[$k] : null;
    }
}
