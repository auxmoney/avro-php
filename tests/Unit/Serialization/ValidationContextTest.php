<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Serialization\ValidationContext;
use PHPUnit\Framework\TestCase;

class ValidationContextTest extends TestCase
{
    private ValidationContext $context;

    protected function setUp(): void
    {
        $this->context = new ValidationContext();
    }

    public function testAddError(): void
    {
        $this->context->addError('First error');
        $this->context->addError('Second error');

        $errors = $this->context->getContextErrors();

        $this->assertCount(2, $errors);
        $this->assertSame('First error', $errors[0]);
        $this->assertSame('Second error', $errors[1]);
    }

    public function testGetContextErrorsReturnsEmptyArrayInitially(): void
    {
        $errors = $this->context->getContextErrors();

        $this->assertEmpty($errors);
    }

    public function testPushAndPopPath(): void
    {
        $this->context->pushPath('field1');
        $this->context->addError('error in field1');

        $this->context->pushPath('subfield');
        $this->context->addError('error in subfield');

        $this->context->popPath();
        $this->context->addError('another error in field1');

        $this->context->popPath();
        $this->context->addError('error at root');

        $errors = $this->context->getContextErrors();

        $this->assertCount(4, $errors);
        $this->assertSame('field1: error in field1', $errors[0]);
        $this->assertSame('field1.subfield: error in subfield', $errors[1]);
        $this->assertSame('field1: another error in field1', $errors[2]);
        $this->assertSame('error at root', $errors[3]);
    }

    public function testPushAndPopContext(): void
    {
        $this->context->addError('root error');

        $this->context->pushContext();
        $this->context->addError('nested error 1');
        $this->context->addError('nested error 2');
        $this->context->popContext(discardErrors: false);

        $errors = $this->context->getContextErrors();

        $this->assertCount(3, $errors);
        $this->assertSame('root error', $errors[0]);
        $this->assertSame('nested error 1', $errors[1]);
        $this->assertSame('nested error 2', $errors[2]);
    }

    public function testPopContextWithDiscardErrors(): void
    {
        $this->context->addError('root error');

        $this->context->pushContext();
        $this->context->addError('nested error 1');
        $this->context->addError('nested error 2');
        $this->context->popContext(discardErrors: true);

        $errors = $this->context->getContextErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('root error', $errors[0]);
    }

    public function testNestedContextsWithPaths(): void
    {
        $this->context->pushPath('users');
        $this->context->pushPath('[0]');

        $this->context->pushContext();
        $this->context->pushPath('name');
        $this->context->addError('invalid name');
        $this->context->popPath();

        $this->context->pushPath('email');
        $this->context->addError('invalid email');
        $this->context->popPath();

        $this->context->popContext(discardErrors: false);

        $errors = $this->context->getContextErrors();

        $this->assertCount(2, $errors);
        $this->assertSame('users.[0].name: invalid name', $errors[0]);
        $this->assertSame('users.[0].email: invalid email', $errors[1]);
    }

    public function testMultipleNestedContexts(): void
    {
        $this->context->addError('level 0 error');

        // First nested context
        $this->context->pushContext();
        $this->context->addError('level 1 error A');

        // Second nested context
        $this->context->pushContext();
        $this->context->addError('level 2 error');
        $this->context->popContext(discardErrors: false);

        $this->context->addError('level 1 error B');
        $this->context->popContext(discardErrors: false);

        $errors = $this->context->getContextErrors();

        $this->assertCount(4, $errors);
        $this->assertSame('level 0 error', $errors[0]);
        $this->assertSame('level 1 error A', $errors[1]);
        $this->assertSame('level 2 error', $errors[2]);
        $this->assertSame('level 1 error B', $errors[3]);
    }

    public function testPartialDiscardInNestedContexts(): void
    {
        $this->context->addError('root error');

        $this->context->pushContext();
        $this->context->addError('keep this');

        $this->context->pushContext();
        $this->context->addError('discard this');
        $this->context->popContext(discardErrors: true); // Discard

        $this->context->addError('keep this too');
        $this->context->popContext(discardErrors: false); // Keep

        $errors = $this->context->getContextErrors();

        $this->assertCount(3, $errors);
        $this->assertSame('root error', $errors[0]);
        $this->assertSame('keep this', $errors[1]);
        $this->assertSame('keep this too', $errors[2]);
    }

    public function testPopContextOnRootLevel(): void
    {
        $this->context->addError('root error');

        // Try to pop context when only at root level
        $this->context->popContext(discardErrors: false);

        // Should still have the root error
        $errors = $this->context->getContextErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('root error', $errors[0]);
    }

    public function testPopPathWhenNoPathExists(): void
    {
        $this->context->popPath(); // Should not cause error
        $this->context->addError('error after pop');

        $errors = $this->context->getContextErrors();
        $this->assertCount(1, $errors);
        $this->assertSame('error after pop', $errors[0]);
    }

    public function testComplexPathStructure(): void
    {
        $this->context->pushPath('record');
        $this->context->pushPath('arrayField');
        $this->context->pushPath('[0]');
        $this->context->pushPath('mapField');
        $this->context->pushPath('[key1]');
        $this->context->pushPath('nestedRecord');
        $this->context->pushPath('value');

        $this->context->addError('deeply nested error');

        $errors = $this->context->getContextErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('record.arrayField.[0].mapField.[key1].nestedRecord.value: deeply nested error', $errors[0]);
    }

    public function testEmptyPathPrefix(): void
    {
        // When no path is set, error should not have prefix
        $this->context->addError('no prefix error');

        $errors = $this->context->getContextErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('no prefix error', $errors[0]);
    }

    public function testMixedPathAndContextOperations(): void
    {
        $this->context->pushPath('root');
        $this->context->addError('root level');

        $this->context->pushContext();
        $this->context->pushPath('nested');
        $this->context->addError('nested level');

        $this->context->pushPath('deeper');
        $this->context->addError('deeper level');
        $this->context->popPath();

        $this->context->popPath();
        $this->context->popContext(discardErrors: false);

        $this->context->popPath();
        $this->context->addError('back at root');

        $errors = $this->context->getContextErrors();

        $this->assertCount(4, $errors);
        $this->assertSame('root: root level', $errors[0]);
        $this->assertSame('root.nested: nested level', $errors[1]);
        $this->assertSame('root.nested.deeper: deeper level', $errors[2]);
        $this->assertSame('back at root', $errors[3]);
    }

    public function testSpecialCharactersInPath(): void
    {
        $this->context->pushPath('field with spaces');
        $this->context->pushPath('[key.with.dots]');
        $this->context->pushPath('field:with:colons');

        $this->context->addError('special chars');

        $errors = $this->context->getContextErrors();

        $this->assertCount(1, $errors);
        $this->assertSame('field with spaces.[key.with.dots].field:with:colons: special chars', $errors[0]);
    }

    public function testLargeNumberOfErrors(): void
    {
        for ($i = 0; $i < 1000; $i++) {
            $this->context->addError("Error {$i}");
        }

        $errors = $this->context->getContextErrors();

        $this->assertCount(1000, $errors);
        $this->assertSame('Error 0', $errors[0]);
        $this->assertSame('Error 999', $errors[999]);
    }

    public function testContextIsolation(): void
    {
        $this->context->addError('main error');

        $this->context->pushContext();
        $this->context->addError('context 1 error');

        $this->context->pushContext();
        $this->context->addError('context 2 error');

        // Get errors from innermost context
        $context2Errors = $this->context->getContextErrors();
        $this->assertCount(1, $context2Errors);
        $this->assertSame('context 2 error', $context2Errors[0]);

        $this->context->popContext(discardErrors: true);

        // Get errors from middle context
        $context1Errors = $this->context->getContextErrors();
        $this->assertCount(1, $context1Errors);
        $this->assertSame('context 1 error', $context1Errors[0]);

        $this->context->popContext(discardErrors: false);

        // Get errors from main context (should include middle context)
        $mainErrors = $this->context->getContextErrors();
        $this->assertCount(2, $mainErrors);
        $this->assertSame('main error', $mainErrors[0]);
        $this->assertSame('context 1 error', $mainErrors[1]);
    }
}
