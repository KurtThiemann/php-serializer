<?php

namespace Aternos\Serializer\Test\Tests;

use Aternos\Serializer\ArrayDeserializer;
use Aternos\Serializer\Exceptions\IncorrectTypeException;
use Aternos\Serializer\Exceptions\MissingPropertyException;
use Aternos\Serializer\Exceptions\UnsupportedTypeException;
use Aternos\Serializer\Serialize;
use Aternos\Serializer\Test\Src\DefaultValueTestClass;
use Aternos\Serializer\Test\Src\IntersectionTestClass;
use Aternos\Serializer\Test\Src\SerializerTestClass;
use Aternos\Serializer\Test\Src\TestClass;
use Aternos\Serializer\Test\Src\UnionIntersectionTestClass;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArrayDeserializer::class)]
#[UsesClass(Serialize::class)]
#[UsesClass(IncorrectTypeException::class)]
#[UsesClass(MissingPropertyException::class)]
#[UsesClass(UnsupportedTypeException::class)]
class DeserializerTest extends TestCase
{
    public function testDeserialize(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize(["name" => "test", "age" =>18]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(18, $testClass->getAge());
        $this->assertSame('test', $testClass->getNotAJsonField());
    }

    public function testDeserializeInvalidClass(): void
    {
        $deserializer = new ArrayDeserializer("non-existant-class");
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Class 'non-existant-class' does not exist.");
        $deserializer->deserialize([]);
    }

    public function testDeserializeAdditionalProperties(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "age" => 18,
            "non-existent-property" => false
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(18, $testClass->getAge());
    }

    public function testDeserializeRenamedProperties(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "age" => 18,
            "changedName" => "other-name"
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(18, $testClass->getAge());
        $this->assertSame('other-name', $testClass->getOriginalName());
    }

    public function testDeserializeMissingProperty(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $this->expectException(MissingPropertyException::class);
        $this->expectExceptionMessage("Missing property '.name' of type 'string'.");
        $deserializer->deserialize(["age" => 18]);
    }

    public function testDeserializeMissingPropertyNotRequired(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize(["name" => "test"]);
        $this->assertSame('test', $testClass->getName());
    }

    public function testDeserializeIncorrectDataType(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $this->expectException(IncorrectTypeException::class);
        $this->expectExceptionMessage("Expected '.age' to be 'int' found: 'eighteen'");
        $deserializer->deserialize([
            "name" => "test",
            "age" => "eighteen"
        ]);
    }

    public function testDeserializeOptionalNotNullable(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "nullable" => 0
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(0, $testClass->getNullable());
    }

    public function testDeserializeOptionalNotNullableNull(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $this->expectException(IncorrectTypeException::class);
        $this->expectExceptionMessage("Expected '.nullable' to be 'int' found: NULL");
        $deserializer->deserialize([
            "name" => "test",
            "nullable" => null
        ]);
    }

    public function testDeserializeUnionTypeBool(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "boolOrInt" => true
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertTrue($testClass->getBoolOrInt());
    }

    public function testDeserializeUnionTypeInt(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "boolOrInt" => 1
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(1, $testClass->getBoolOrInt());
    }

    public function testDeserializeUnionTypeString(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $this->expectException(IncorrectTypeException::class);
        $this->expectExceptionMessage("Expected '.boolOrInt' to be 'int|bool' found: 'not-either'");
        $deserializer->deserialize([
            "name" => "test",
            "boolOrInt" => "not-either"
        ]);
    }

    public function testDeserializeSecondClass(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "secondTestClass" => ["y" => 123]
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(123, $testClass->getSecondTestClass()?->getY());
    }

    public function testDeserializeMixed(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "mixed" => ["y" => 123]
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(["y" => 123], $testClass->getMixed());
    }

    public function testDeserializeFloat(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "float" => 1.5
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(1.5, $testClass->getFloat());
    }

    public function testDeserializeIntAsFloat(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "float" => 1
        ]);
        $this->assertSame('test', $testClass->getName());
        $this->assertSame(1.0, $testClass->getFloat());
    }

    public function testDeserializeSecondClassNotArray(): void
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $this->expectException(IncorrectTypeException::class);
        $this->expectExceptionMessage("Expected '.secondTestClass' to be 'Aternos\Serializer\Test\Src\SecondTestClass' found: 'y'");
        $deserializer->deserialize([
            "name" => "test",
            "secondTestClass" => "y"
        ]);
    }

    public function testDeserializeIntersection(): void
    {
        $deserializer = new ArrayDeserializer(IntersectionTestClass::class);
        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage("Unsupported type 'Throwable&Iterator' for property '.x': Intersection types are not supported");
        $deserializer->deserialize([
            "x" => "123"
        ]);
    }

    public function testDeserializeUnionIntersection(): void
    {
        $deserializer = new ArrayDeserializer(UnionIntersectionTestClass::class);
        $this->expectException(UnsupportedTypeException::class);
        $this->expectExceptionMessage("Unsupported type 'Throwable&Iterator' for property '.x': Intersection types are not supported");
        $deserializer->deserialize([
            "x" => "123"
        ]);
    }

    public function testDeserializeArray()
    {
        $deserializer = new ArrayDeserializer(TestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "age" => 15,
            "array" => [1, 2, 3]
        ]);
        $this->assertSame([1, 2, 3], $testClass->getArray());
    }

    public function testDeserializeNullableObjects()
    {
        $deserializer = new ArrayDeserializer(SerializerTestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "age" => 15,
            "secondTestClass" => null,
            "testClass" => null
        ]);
        $this->assertNull($testClass->getSecondTestClass());
    }

    public function testDeserializeAllowNull()
    {
        $deserializer = new ArrayDeserializer(SerializerTestClass::class);
        $testClass = $deserializer->deserialize([
            "name" => "test",
            "age" => 15,
            "nullable" => null
        ]);
        $this->assertFalse(isset($testClass->nullable));
    }

    public function testDeserializeIntWithDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithoutDefault" => 1,
            "nullableIntWithoutDefault" => 1,
            "stringWithDefault" => "test",
            "stringWithoutDefault" => "test",
            "nullableStringWithoutDefault" => "test"
        ]);
        $this->assertTrue(isset($testClass->intWithDefault));
        $this->assertSame(0, $testClass->intWithDefault);
    }

    public function testDeserializeIntWithoutDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithDefault" => 1,
            "nullableIntWithoutDefault" => 1,
            "stringWithDefault" => "test",
            "stringWithoutDefault" => "test",
            "nullableStringWithoutDefault" => "test"
        ]);
        $this->assertFalse(isset($testClass->intWithoutDefault));
    }

    public function testDeserializeNullableIntWithoutDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithDefault" => 1,
            "intWithoutDefault" => 1,
            "stringWithDefault" => "test",
            "stringWithoutDefault" => "test",
            "nullableStringWithoutDefault" => "test"
        ]);
        $this->assertFalse(isset($testClass->nullableIntWithoutDefault));
    }

    public function testDeserializeStringWithDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithDefault" => 1,
            "intWithoutDefault" => 1,
            "nullableIntWithoutDefault" => 1,
            "stringWithoutDefault" => "test",
            "nullableStringWithoutDefault" => "test"
        ]);
        $this->assertTrue(isset($testClass->stringWithDefault));
        $this->assertSame("", $testClass->stringWithDefault);
    }

    public function testDeserializeStringWithoutDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithDefault" => 1,
            "intWithoutDefault" => 1,
            "nullableIntWithoutDefault" => 1,
            "stringWithDefault" => "test",
            "nullableStringWithoutDefault" => "test"
        ]);
        $this->assertFalse(isset($testClass->stringWithoutDefault));
    }

    public function testDeserializeNullableStringWithoutDefault(): void
    {
        $deserializer = new ArrayDeserializer(DefaultValueTestClass::class);
        $testClass = $deserializer->deserialize([
            "intWithDefault" => 1,
            "intWithoutDefault" => 1,
            "nullableIntWithoutDefault" => 1,
            "stringWithDefault" => "test",
            "stringWithoutDefault" => "test"
        ]);
        $this->assertFalse(isset($testClass->nullableStringWithoutDefault));
    }
}