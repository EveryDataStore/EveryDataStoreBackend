<?php

namespace EveryDataStore\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use EveryDataStore\Model\EveryConfiguration;

class EveryConfigurationTest extends SapphireTest
{
    protected static $fixture_file = '../../fixture/EveryDataStoreTest.yml';
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->logInWithPermission('ADMIN');
    }
    
    /**
     * Simple test for object creation 
     */
    public function testEveryConfigurationCreation() {
        $testObj = $this->objFromFixture(EveryConfiguration::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
    }
    
    /**
     * Simple test for admin permissions
     */
    public function testAdminPermissions() {
        $testObj = $this->objFromFixture(EveryConfiguration::class, 'FirstObj');
        $this->assertTrue($testObj->canView(), 'Admin should be able to view EveryConfiguration.');
        $this->assertTrue($testObj->canCreate(), 'Admin should be able to create EveryConfiguration.');
        $this->assertTrue($testObj->canEdit(), 'Admin should be able to edit EveryConfiguration.');
        $this->assertTrue($testObj->canDelete(), 'Admin should be able to edit EveryConfiguration.');
    }
    
    
    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Title' => 'My Title',
                'Value' => 'My Value'
            ]
        ];

        return $data[$k] ? $data[$k] : null;
    }
}