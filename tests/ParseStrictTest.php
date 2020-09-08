<?php

namespace arcowebsites\utils\tests;

use PHPUnit\Framework\TestCase;
use arcowebsites\utils\tests\model\ExampleModel;

class BoletoTest extends TestCase {

    public function testParseStrict() {
        $object = new \stdClass();
        $object->property1 = "Test";
        $object->property2 = "false";

        print "\n";
        print var_dump(ExampleModel::parse($object, true));
        $this->assertTrue(true);
    }

}
