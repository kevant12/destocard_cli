<?php

namespace App\Tests\Form;

use App\Entity\Users;
use App\Form\RegisterFormType;
use Symfony\Component\Form\Test\TypeTestCase;

class RegisterFormTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $expected = new Users();
        $expected->setEmail('test@example.com');
        $expected->setFirstname('John');
        $expected->setLastname('Doe');
        $expected->setCivility('Mr');
        $expected->setPhoneNumber('0123456789');
        $expected->setPassword('password123');

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected->getEmail(), $model->getEmail());
        $this->assertEquals($expected->getFirstname(), $model->getFirstname());
        $this->assertEquals($expected->getLastname(), $model->getLastname());
        $this->assertEquals($expected->getCivility(), $model->getCivility());
        $this->assertEquals($expected->getPhoneNumber(), $model->getPhoneNumber());
        $this->assertEquals($expected->getPassword(), $model->getPassword());
    }

    public function testSubmitInvalidEmail(): void
    {
        $formData = [
            'email' => 'invalid-email',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testSubmitEmptyEmail(): void
    {
        $formData = [
            'email' => '',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('email')->getErrors()->count() > 0);
    }

    public function testSubmitMismatchedPasswords(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'differentpassword'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('password')->getErrors()->count() > 0);
    }

    public function testSubmitShortPassword(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => '123',
                'second' => '123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('password')->getErrors()->count() > 0);
    }

    public function testSubmitEmptyFirstname(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => '',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('firstname')->getErrors()->count() > 0);
    }

    public function testSubmitEmptyLastname(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => '',
            'civility' => 'Mr',
            'phoneNumber' => '0123456789',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('lastname')->getErrors()->count() > 0);
    }

    public function testSubmitEmptyPhoneNumber(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => '',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('phoneNumber')->getErrors()->count() > 0);
    }

    public function testSubmitInvalidPhoneNumber(): void
    {
        $formData = [
            'email' => 'test@example.com',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'civility' => 'Mr',
            'phoneNumber' => 'abc123',
            'password' => [
                'first' => 'password123',
                'second' => 'password123'
            ]
        ];

        $model = new Users();
        $form = $this->factory->create(RegisterFormType::class, $model);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('phoneNumber')->getErrors()->count() > 0);
    }

    public function testFormRendering(): void
    {
        $form = $this->factory->create(RegisterFormType::class);

        $view = $form->createView();
        $children = $view->children;

        $this->assertArrayHasKey('email', $children);
        $this->assertArrayHasKey('firstname', $children);
        $this->assertArrayHasKey('lastname', $children);
        $this->assertArrayHasKey('civility', $children);
        $this->assertArrayHasKey('phoneNumber', $children);
        $this->assertArrayHasKey('password', $children);
    }
} 