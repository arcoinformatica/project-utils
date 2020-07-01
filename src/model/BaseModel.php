<?php

namespace arcowebsites\utils\model;

use JsonMapper;
use ReflectionClass;
use ReflectionProperty;
use Exception;
use JsonSerializable;

abstract class BaseModel implements JsonSerializable {

    private $required = [];

    /**
     * Realiza o parse de um dado para um objeto tipado
     * 
     * @param array|object $data
     * @return object
     */
    public static function parse($data): object {
        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;
        $mapper->bRemoveUndefinedAttributes = true;
        $mapper->bStrictNullTypes = false;
        $relection = new ReflectionClass(get_called_class());
        return $mapper->map(is_array($data) ? ((object) $data) : $data, $relection->newInstanceWithoutConstructor());
    }

    /**
     * 
     * @param mixed $data
     * @throws Exception
     */
    public static function validate($data) {
        
    }

    public function jsonSerialize() {
        $properties = array();
        $rc = new ReflectionClass($this);
        do {
            $rp = array();
            /** @var ReflectionProperty $p */
            foreach ($rc->getProperties() as $p) {
                $p->setAccessible(true);
                $rp[$p->getName()] = $p->getValue($this);
                if (!is_null($rp[$p->getName()]) && preg_match('/@var\s+([^\s]+)/', $p->getDocComment(), $matches)) {
                    list(, $type) = $matches;
                    $types = explode("|", $type);
                    if (in_array($types[0], ["boolean", "bool", "integer", "int", "float", "double", "string", "array", "object", "null"])) {
                        settype($rp[$p->getName()], $types[0]);
                    }
                } else {
                    unset($rp[$p->getName()]);
                }
            }
            $properties = array_merge($rp, $properties);
        } while ($rc = $rc->getParentClass());

        unset($properties['required']);

        return $properties;
    }

}
