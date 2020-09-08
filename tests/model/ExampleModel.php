<?php

namespace arcowebsites\utils\tests\model;

use arcowebsites\utils\model\BaseModel;

class ExampleModel extends BaseModel {

    /**
     *
     * @var string|null 
     */
    private $property1;

    /**
     *
     * @var bool|null 
     */
    private $property2;

    function getProperty1(): ?string {
        return $this->property1;
    }

    function getProperty2(): ?bool {
        return $this->property2;
    }

    function setProperty1(?string $property1): void {
        $this->property1 = $property1;
    }

    function setProperty2(?bool $property2): void {
        $this->property2 = $property2;
    }

}
