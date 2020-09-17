<?php

namespace arcowebsites\utils\tests;

use PHPUnit\Framework\TestCase;
use arcowebsites\utils\tests\model\ExampleModel;

class ParseTest extends TestCase {

    public function testParse() {
        $object = new \stdClass();
        $object->property1 = "Test";
        $object->property2 = "false";
        $object->property3 = "";

        print "\n";
        dump(ExampleModel::parse($object));
        $this->assertTrue(true);
    }

}
