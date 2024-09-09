<?php

namespace EveryDataStore\Tests\Model;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Security\Member;
use EveryDataStore\Model\Note;


class NoteTest extends SapphireTest {

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
    public function testNoteCreation() {
        $testObj = $this->objFromFixture(Note::class, 'FirstObj');
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
                'Content' => 'My Content',
                'RecordSetItemID' => 1
            ]
        ];

        return $data[$k] ? $data[$k] : null;
    }
}
