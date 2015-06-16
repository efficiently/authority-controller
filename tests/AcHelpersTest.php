<?php

use Mockery as m;

class AcHelpersTest extends AcTestCase
{

    public function testArrayExtractOptions()
    {
        $args = ['foo' => 'bar', 'value'];
        $options = array_extract_options($args);
        $expect = ['foo' => 'bar'];
        $this->assertEquals($options, $expect);
    }

    public function testCompactProperty()
    {
        $expectName = "Best movie";
        $myProduct = new AcProduct;
        $myProduct->setName($expectName);
        $compactedArray = compact_property($myProduct, 'name');

        $this->assertArrayHasKey('name', $compactedArray);
        $this->assertEquals($expectName, $compactedArray['name']);
    }

    public function testCompactProperties()
    {
        $expectName = "Best movie";
        $expectPrice = "15";
        $myProduct = new AcProduct;
        $myProduct->setName($expectName);
        $myProduct->setPrice($expectPrice);
        $compactedArray = compact_property($myProduct, 'name', 'price');

        $this->assertArrayHasKey('name', $compactedArray);
        $this->assertArrayHasKey('price', $compactedArray);
        $this->assertEquals($expectName, $compactedArray['name']);
        $this->assertEquals($expectPrice, $compactedArray['price']);
    }

    public function testGetClassname()
    {
        $mock = m::mock('Project');
        $this->assertNotEquals('Project', get_class($mock));
        $this->assertEquals('Project', get_classname($mock));

        $mockNamespace = m::mock('Sub\Task');
        $this->assertNotEquals('Sub\Task', get_class($mockNamespace));
        $this->assertEquals('Sub\Task', get_classname($mockNamespace));
    }

    public function testAcTrans()
    {
        $defaultErrorMessage = ac_trans("messages.unauthorized.default");
        $this->assertEquals('You are not authorized to access this page.', $defaultErrorMessage);

        App::setLocale('fr');
        $defaultErrorMessageFr = ac_trans("messages.unauthorized.default");

        $this->assertNotEquals('You are not authorized to access this page.', $defaultErrorMessageFr);
        $this->assertEquals("Vous n'êtes pas autorisé à accéder à cette page.", $defaultErrorMessageFr);
    }

    public function testRespondTo()
    {
        $mock = m::mock('Project');
        $this->assertFalse(method_exists($mock, 'toto'));
        $this->assertFalse(respond_to($mock, 'toto'));

        $mock->shouldReceive('toto');
        $this->assertFalse(method_exists($mock, 'toto'));
        $this->assertTrue(respond_to($mock, 'toto'));
    }

    public function testGetProperty()
    {
        $category = new AcCategory;
        $privatePropertyName = 'privateProperty';
        $this->assertNotEquals('public property', get_property($category, $privatePropertyName));
        $this->assertEquals('private property', get_property($category, $privatePropertyName));

    }

    public function testSetProperty()
    {
        $category = new AcCategory;
        $privatePropertyName = 'privateProperty';
        set_property($category, $privatePropertyName, 'updated private property');
        $this->assertNotEquals('private property', get_property($category, $privatePropertyName));
        $this->assertEquals('updated private property', get_property($category, $privatePropertyName));
    }

    public function testInvokeMethod()
    {
        $category = new AcCategory;

        $this->assertTrue(is_callable([$category, 'publicMethod']));

        $privateMethodName = 'privateMethod';
        $this->assertFalse(is_callable([$category, $privateMethodName]));
        $this->assertEquals('private method', invoke_method($category, $privateMethodName));
    }
}
