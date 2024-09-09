<?php

namespace EveryDataStore\Tests\Model\FormField;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\RecordSet\Form\FormField;

class FormFieldTest extends SapphireTest {
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
    public function testFormFieldCreation() {
        $testObj = $this->objFromFixture(FormField::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
    }

    /**
     * Testing if the a dataStore will create and if the other relations will be deleted.
     */
    public function testFormFieldDeletion() {
        $testObj = $this->objFromFixture(FormField::class, 'FirstObj');
        $testObj->delete();
        $this->assertEquals(0, $testObj->Settings()->Count(), 'FormField Setting Coount');
        $this->assertEquals(0, $testObj->ItemData()->Count(), 'FormField ItemData Count');
    }

    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Sort' => 1,
                'ColumnID' => 1
            ]
        ];
        return $data[$k] ? $data[$k] : null;
    }
}
