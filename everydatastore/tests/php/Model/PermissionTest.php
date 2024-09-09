<?php

namespace EveryDataStore\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Member;
use SilverStripe\Security\Group;
use EveryDataStore\Model\RecordSet\RecordSet;

class PermissionTest extends SapphireTest
{
    protected static $fixture_file = '../../fixture/EveryDataStoreTest.yml';
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void {
        parent::setUp(); 
    }
    
    /**
     * Simple test for object creation 
     */
    public function testPermissionCreation() {
        $this->logInWithPermission('ADMIN');
        $testObj = $this->objFromFixture(Permission::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
        $this->logOut();
    }
     
    
    public function testPermissioCanCreate(){
        $this->objFromFixture(Group::class, 'AppManager');
        $appManager = $this->objFromFixture(Member::class, 'AppManager');
        $this->objFromFixture(Permission::class, 'FirstObj');

        $this->logInWithPermission('APPMANAGER');
        $recordSet = $this->objFromFixture(RecordSet::class, 'FirstObj');
        $this->assertTrue($recordSet->canCreate($appManager), 'App Manager should  be able to create category.');

        $this->logOut();
    }
    
    
    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Code' => 'CREATE_RECORDITEM',
                'Type' => 1,
                'GroupID' => 2
            ]
        ];
        return $data[$k] ? $data[$k] : null;
    }
}