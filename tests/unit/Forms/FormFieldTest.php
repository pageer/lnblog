<?php

namespace LnBlog\Tests\Forms;

use BasePages;
use LnBlog\Forms\FormField;
use LnBlog\Forms\Renderers\InputRenderer;
use LnBlog\Tests\LnBlogBaseTestCase;
use Prophecy\Argument;

class FormFieldTest extends LnBlogBaseTestCase
{
    public function testValidate_WhenNoValidator_ReturnsTrue() {
        $f = new FormField('foo');

        $result = $f->validate();

        $this->assertEquals('foo', $f->getName());
        $this->assertTrue($result);
    }

    public function testValidate_WhenValidatorPasses_ReturnsTrue() {
        $f = new FormField('foo');
        $f->setValidator(
            function (string $value) {
                return [];
            }
        );

        $result = $f->validate();
        $validated = $f->isValidated();

        $this->assertTrue($result);
        $this->assertTrue($validated);
    }

    public function testValidate_WhenNoValidator_ReturnsFalseAndSetsErrors() {
        $f = new FormField(
            'foo', 
            null,
            function (string $value) {
                return ['error'];
            }
        );

        $result = $f->validate();
        $validated = $f->isValidated();
        $errors = $f->getErrors();

        $this->assertFalse($result);
        $this->assertFalse($validated);
        $this->assertEquals(['error'], $errors);
    }

    public function testGetValue_WhenNoConverter_ReturnsRawValue() {
        $f = new FormField('foo');

        $f->setRawValue('bar');
        $value = $f->getValue();
        $raw_value = $f->getRawValue();

        $this->assertEquals('bar', $value);
        $this->assertEquals('bar', $raw_value);
    }

    public function testGetValue_WhenConverterSet_ReturnsConvertedValue() {
        $f = new FormField('foo');
        $f->setConverter(
            function (string $value) {
                return "foo" . $value;
            }
        );

        $f->setRawValue('bar');
        $value = $f->getValue();
        $raw_value = $f->getRawValue();

        $this->assertEquals('foobar', $value);
        $this->assertEquals('bar', $raw_value);
    }

    public function testRender_WithOptions_SetsRequiredOptions() {
        $base_pages = $this->prophet->prophesize(BasePages::class);
        $renderer = $this->prophet->prophesize(InputRenderer::class);

        $renderer->render(Argument::any(), Argument::any())->willReturn('some HTML');
        $renderer->setLabel('Foo')->shouldBeCalled();
        $renderer->setData('SEPARATE_LABEL', true)->shouldBeCalled();
        $renderer->setData('LABEL_AFTER', false)->shouldBeCalled();
        $renderer->setData('SUPPRESS_ERRORS', false)->shouldBeCalled();
        $renderer->setAttributes(['id' => 'foo'])->shouldBeCalled();

        $f = new FormField('foo', $renderer->reveal());
        $result = $f->render(
            $base_pages->reveal(),
            [
                'id' => 'foo',
                'label' => 'Foo',
                'sep_label' => true,
                'noerror' => false,
            ]
        );

        $this->assertEquals('some HTML', $result);
    }

    public function testRenderer_WithoutOptions_SetsRequiredOptionsNotAttributes() {
        $base_pages = $this->prophet->prophesize(BasePages::class);
        $renderer = $this->prophet->prophesize(InputRenderer::class);

        $renderer->render(Argument::any(), Argument::any())->willReturn('some HTML');
        $renderer->setData('SEPARATE_LABEL', false)->shouldBeCalled();
        $renderer->setData('LABEL_AFTER', false)->shouldBeCalled();
        $renderer->setData('SUPPRESS_ERRORS', false)->shouldBeCalled();
        $renderer->setAttributes(Argument::any())->shouldNotBeCalled();

        $f = new FormField('foo');
        $f->setRenderer($renderer->reveal());
        $result = $f->render($base_pages->reveal());

        $this->assertEquals('some HTML', $result);
    }
}
