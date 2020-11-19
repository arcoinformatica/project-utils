<?php

namespace arcowebsites\utils\model;

use JsonMapper;
use ReflectionClass;
use ReflectionProperty;
use JsonSerializable;
use DateTime;

abstract class BaseModel implements JsonSerializable {

    /**
     * Remove propriedades/atributos nulos ao converter o objeto para json.
     *
     * @var bool 
     */
    protected $removeNullProperties = false;

    /**
     * Realiza o parse de um objeto ou uma lista de objetos genéricos
     * para um objeto tipado ou uma lista de objetos tipados.
     * 
     * @param array|object $data objecto ou array de objetos a serem parseados
     * @return object|array objeto tipado ou lista de objetos tipados
     */
    public static function parse($data) {
        $reflection = new ReflectionClass(get_called_class());

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::filter($value, $reflection);
            }
        } else {
            $data = self::filter($data, $reflection);
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
     * Analisa o objeto antes do processo de tipagem, e realiza
     * um tratamento nos valores antes de fazer o parse.
     * - Strings "false" e "true" para true e false;
     * - Number "" (string vazia) para null;
     * 
     * @param object $object objeto genérico
     * @param ReflectionClass $reflection
     * @return object objeto com propriedades filtradas
     */
    private static function filter(object $object, ReflectionClass $reflection): object {
        foreach ($object as $key => $value) {
            if (!is_null($value)) {
                /** @var ReflectionProperty $property */
                foreach ($reflection->getProperties() as $property) {
                    if (!in_array($property->getName(), ['removeNullProperties'])) {
                        $matches = [];
                        preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches);
                        list(, $type) = $matches;
                        $types = explode("|", $type);

                        $property->setAccessible(true);
                        if (in_array($types[0], ["boolean", "bool"])) { // Converte strings "false" ou "true" para bool (null vira false)
                            $value = property_exists($object, $property->getName()) ? filter_var($object->{$property->getName()}, FILTER_VALIDATE_BOOLEAN) : false;
                            $property->setValue($object, $value);
                        } else if (in_array($types[0], ["integer", "int", "float", "double"]) && $value === "") { // Converte propriedades numericas com string vazia para null
                            $property->setValue($object, null);
                        }
                        $property->setAccessible(false);
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

                $matches = [];
                $has_var_annotation = preg_match('/@var\s+([^\s]+)/', $p->getDocComment(), $matches);

                if (!$has_var_annotation || ($this->removeNullProperties && is_null($rp[$p->getName()]))) {
                    unset($rp[$p->getName()]);
                } else if (!is_null($rp[$p->getName()])) {
                    list(, $type) = $matches;
                    $types = explode("|", $type);
                    if (in_array($types[0], ["boolean", "bool", "integer", "int", "float", "double", "string"])) {
                        settype($rp[$p->getName()], $types[0]);
                    } else if ($types[0] == DateTime::class && $rp[$p->getName()] instanceof DateTime) {
                        $rp[$p->getName()] = $rp[$p->getName()]->format(DateTime::ISO8601);
                    }
                }
            }
            $properties = array_merge($rp, $properties);
        } while ($rc = $rc->getParentClass());

        unset($properties['removeNullProperties']);

        return $properties;
    }

}
