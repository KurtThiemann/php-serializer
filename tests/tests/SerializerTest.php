<?php

namespace Aternos\Serializer\Test\Tests;

use Aternos\Serializer\ArraySerializer;
use Aternos\Serializer\Exceptions\SerializationIncorrectTypeException;
use Aternos\Serializer\Exceptions\SerializationMissingPropertyException;
use Aternos\Serializer\Json\PropertyJsonSerializer;
use Aternos\Serializer\Serialize;
use Aternos\Serializer\Test\Src\SecondTestClass;
use Aternos\Serializer\Test\Src\SerializerTestClass;
use Aternos\Serializer\Test\Src\TestClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArraySerializer::class)]
#[UsesClass(PropertyJsonSerializer::class)]
#[UsesClass(Serialize::class)]
#[UsesClass(SerializationIncorrectTypeException::class)]
#[UsesClass(SerializationMissingPropertyException::class)]
class SerializerTest extends TestCase
{
    public function testSerialize(): void
    {
        $testClass = new SerializerTestClass();
        $testClass->setName('test');
        $serializer = new ArraySerializer();
        $this->assertSame([
            "name" => "test",
            "age" => 0,
            "notNullable" => "asd",
        ], $serializer->serialize($testClass));
    }

    public function testSerializeNoName(): void
    {
        $testClass = new SerializerTestClass();
        $serializer = new ArraySerializer();
        $this->expectException(SerializationMissingPropertyException::class);
        $serializer->serialize($testClass);
    }

    public function testSerializeNotNull(): void
    {
        $testClass = new SerializerTestClass();
        $testClass->setName('test');
        $testClass->setNotNullable(null);
        $serializer = new ArraySerializer();
        $this->expectException(SerializationIncorrectTypeException::class);
        $serializer->serialize($testClass);
    }

    public function testSerializeOtherClass(): void
    {
        $testClass = new SerializerTestClass();
        $testClass->setName('test');
        $secondClass = new SecondTestClass();
        $secondClass->setY(1);
        $testClass->setSecondTestClass($secondClass);
        $serializer = new ArraySerializer();
        $this->assertSame([
            'name' => 'test',
            'age' => 0,
            'notNullable' => 'asd',
            'secondTestClass' => [
                'y' => 1,
            ],
        ], $serializer->serialize($testClass));
    }

    public function testSerializeOtherClassJsonSerializable(): void
    {
        $testClass = new SerializerTestClass();
        $testClass->setName('test');
        $otherTestClass = new TestClass();
        $otherTestClass->setName('test');
        $otherTestClass->setNullable(1);
        $testClass->setTestClass($otherTestClass);
        $serializer = new ArraySerializer();
        $this->assertSame([
            'name' => 'test',
            'age' => 0,
            'notNullable' => 'asd',
            'testClass' => [
                'name' => 'test',
                'age' => 0,
                'changedName' => null,
                'nullable' => 1,
                'boolOrInt' => false,
                'secondTestClass' => null,
                'mixed' => null,
                'float' => null,
                'array' => null,
            ],
        ], $serializer->serialize($testClass));
    }
}