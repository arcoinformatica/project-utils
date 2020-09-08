<?php

namespace arcowebsites\utils\model;

use JsonMapper;
use ReflectionClass;
use ReflectionProperty;
use JsonSerializable;

abstract class BaseModel implements JsonSerializable {

    /**
     * Realiza o parse de um objeto ou uma lista de objetos genéricos
     * para um objeto tipado ou uma lista de objetos tipados.
     * 
     * @param array|object $data objecto ou array de objetos a serem parseados
     * @param bool $strict_bool ativa o parse de uma string "false" ou
     * "true" para bool (Ex.: $_POST com $_POST['property'] == "false").
     * @return object|array objeto tipado ou lista de objetos tipados
     */
    public static function parse($data, bool $strict_bool = false) {
        $reflection = new ReflectionClass(get_called_class());

        if ($strict_bool) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = self::strictBool($value, $reflection);
                }
            } else {
                $data = self::strictBool($data, $reflection);
            }
        }

        $mapper = new JsonMapper();
        $mapper->bIgnoreVisibility = true;
        $mapper->bRemoveUndefinedAttributes = true;
        $mapper->bStrictNullTypes = false;

        if (is_array($data)) {
            return $mapper->mapArray($data, array(), get_called_class());
        } else {
            return $mapper->map($data, $reflection->newInstanceWithoutConstructor());
        }
    }

    /**
     * Analisa o objeto antes do processo de tipagem, e procura
     * por propriedades bool ou boolean em formato string.
     * 
     * @param object $object objeto genérico
     * @param ReflectionClass $reflection
     * @return object objeto com propriedades boolean setadas
     */
    private static function strictBool(object $object, ReflectionClass $reflection): object {
        foreach ($object as $key => $value) {
            if (!is_null($value) && !empty($value)) {
                /** @var ReflectionProperty $property */
                foreach ($reflection->getProperties() as $property) {
                    if ($key == $property->getName()) {
                        $matches = [];
                        preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches);
                        list(, $type) = $matches;
                        $types = explode("|", $type);

                        if (in_array($types[0], ["boolean", "bool"])) {
                            $property->setAccessible(true);
                            $property->setValue($object, filter_var($object->{$property->getName()}, FILTER_VALIDATE_BOOLEAN));
                            $property->setAccessible(false);
                        }
                    }
                }
            }
        }

        return $object;
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
