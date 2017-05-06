<?php
/**
 * Created by PhpStorm.
 * User: Anton
 * Date: 07.05.2017
 * Time: 19:54
 */

namespace TriAn\IqoTest\core;


class Validator extends \JsonSchema\Validator
{
    /**
     * @var \stdClass
     */
    protected static $schema;

    public function validateWithDefaultSchema(&$value)
    {
        if (!isset($this::$schema)) {
            $this::$schema = (object)['$ref' => 'file://' . realpath( __DIR__ . '/schema/request_body.json')];
        }
        $this->validate($value, $this::$schema);
    }
}