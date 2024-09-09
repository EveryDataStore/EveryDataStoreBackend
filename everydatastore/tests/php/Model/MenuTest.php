<?php

namespace EveryDataStore\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\Menu;


class MenuTest extends SapphireTest {

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
     * Simple test for object creation 
     */
    public function testMenuCreation() {
        $testObj = $this->objFromFixture(Menu::class, 'FirstObj');
        foreach ($this->getAssertEqualsData('FirstObj') as $k => $v) {
            $this->assertEquals($v, $testObj->{$k});
        }
    }
 
    /**
     * Defines nice array data for testing
     * @param string $k
     * @return array
     */
    private function getAssertEqualsData($k) {
        $data = ['FirstObj' => [
                'Title' => 'My Menu',
                'Active' => true,
                'AdminMenu' => false,
                'UserMenu' => false,
                'Controller' => '',
                'Action' => '',
                'ActionID' => '',
                'ActionOtherID' => '',
                'Icon' => 'fa fa-percent',
                'ParentID' => 0,
                'BadgeEndpoint' => ''
            ],'SecondObj' => [
                'Title' => 'My Menu 2',
                'Active' => true,
                'AdminMenu' => false,
                'UserMenu' => false,
                'Controller' => 'record',
                'Action' => 'items',
                'ActionID' => 'RecordSet Slug',
                'ActionOtherID' => 'other id',
                'Icon' => 'fa fa-user',
                'BadgeEndpoint' => ''
            ]
        ];

        return $data[$k] ? $data[$k] : null;
    }
}
