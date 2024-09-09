<?php

namespace EveryDataStore\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\DataStore;


class DataStoreTest extends SapphireTest {
    protected static $fixture_file = '../../fixture/EveryDataStoreTest.yml';
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }
    protected function setUp(): void {
        parent::setUp();
        $me = Member::get()->first();
        $this->logInAs($me);
    }

    /**
     * Testing if the a dataStore will create and if the other 
     * functionality in background like creation of menus, groups and configurations are also working.
     * After Creation of dataStore there should be the Relationsdata created:
     * - 8 menu items
     * - 7 configuration items
     * - 1 group item
     */
    public function testDataStoreCreation() {
        $testObj = $this->objFromFixture(DataStore::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
        $this->assertEquals(8, $testObj->Menu()->count(), 'DataStore Menu count');
        $this->assertEquals(9, $testObj->Configurations()->count(), 'DataStore Configurations count');
        $this->assertEquals(1, $testObj->Groups()->count(), 'DataStore Groups count');
    }

    /**
     * Testing if the a dataStore will create and if the other relations will be deleted.
     */
    public function testDataStoreDeletion() {
        $me = Member::get()->first();
        $this->logInAs($me);
        $testObj = $this->objFromFixture(DataStore::class, 'FirstObj');
        $testObj->delete();
        $this->assertEquals(0, $testObj->Menu()->count(), 'DataStore Menu count');
        $this->assertEquals(0, $testObj->Configurations()->count(), 'DataStore Configurations count');
        $this->assertEquals(0, $testObj->Groups()->count(), 'DataStore Groups count');
    }

    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Active' => true,
                'Title' => 'My DataStore',
                'StorageAllowedSize' => 550000,
                'StorageCurrentSize' => 0,
                'UploadAllowedExtensions' => '["jpeg","jpg","pdf","png"]',            ]
        ];
        return $data[$k] ? $data[$k] : null;
    }
}
