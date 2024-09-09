<?php

namespace EveryDataStore\Tests\Model\RecordSet;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\RecordSet\RecordSet;

class RecordSetTest extends SapphireTest {
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
    public function testRecordCreation() {
        $testObj = $this->objFromFixture(RecordSet::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
    }

    /**
     * Testing if the a dataStore will create and if the other relations will be deleted.
     */
    public function testRecordDeletion() {
        $testObj = $this->objFromFixture(RecordSet::class, 'FirstObj');
        $testObj->delete();
        $this->assertEquals(0, $testObj->Items()->Count(), 'Record Items count');
        $this->assertEquals(0, $testObj->Groups()->Count(), 'Record Groups count');
    }

    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Active' => true,
                'Title' => 'My Record',
                'ShowInMenu' => true,
                'AllowUpload' => true,
                'FrontendTapedForm' => false,
                'OpenFormInDialog' => false,
                'DataStoreID' => 1
            ]
        ];
        return $data[$k] ? $data[$k] : null;
    }
}
