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
     * @param bool $strict_bool ativa o parse de uma string "false" ou
     * "true" para bool (Ex.: $_POST com $_POST['property'] == "false").
     * @return object|array objeto tipado ou lista de objetos tipados
     */
    public static function parse($data, bool $strict_bool = false) {
        $reflection = new ReflectionClass(get_called_class());

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::filter($value, $reflection, $strict_bool);
            }
        } else {
            $data = self::filter($data, $reflection, $strict_bool);
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
     * 
     * @param object $object objeto genérico
     * @param ReflectionClass $reflection
     * @param bool $strict_bool Converter strings "false" ou "true" para bool?
     * @return object objeto com propriedades filtradas
     */
    private static function filter(object $object, ReflectionClass $reflection, bool $strict_bool): object {
        foreach ($object as $key => $value) {
            if (!is_null($value)) {
                /** @var ReflectionProperty $property */
                foreach ($reflection->getProperties() as $property) {
                    if ($key == $property->getName()) {
                        $matches = [];
                        preg_match('/@var\s+([^\s]+)/', $property->getDocComment(), $matches);
                        list(, $type) = $matches;
                        $types = explode("|", $type);

                        $property->setAccessible(true);
                        if (in_array($types[0], ["boolean", "bool"]) && $strict_bool) { // Converte strings "false" ou "true" para bool 
                            $property->setValue($object, filter_var($object->{$property->getName()}, FILTER_VALIDATE_BOOLEAN));
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
                } else {
                    list(, $type) = $matches;
                    $types = explode("|", $type);
                    if (in_array($types[0], ["boolean", "bool", "integer", "int", "float", "double", "string", "array", "object", "null"]) && !is_null($rp[$p->getName()])) {
                        settype($rp[$p->getName()], $types[0]);
                    } else if ($types[0] == DateTime::class && !is_null($rp[$p->getName()]) && $rp[$p->getName()] instanceof DateTime) {
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
