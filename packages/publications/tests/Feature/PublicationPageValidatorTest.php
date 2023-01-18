<?php

declare(strict_types=1);

namespace Hyde\Publications\Testing\Feature;

use Hyde\Publications\Actions\PublicationPageValidator;
use Hyde\Publications\Models\PublicationType;
use Hyde\Testing\TestCase;
use Illuminate\Validation\ValidationException;

/**
 * @covers \Hyde\Publications\Actions\PublicationPageValidator
 */
class PublicationPageValidatorTest extends TestCase
{
    public function testValidatePageFile()
    {
        $this->directory('test-publication');
        $publicationType = new PublicationType('test-publication', fields: [
            ['name' => 'myField', 'type' => 'string'],
            ['name' => 'myNumber', 'type' => 'integer'],
        ]);
        $publicationType->save();

        $this->file('test-publication/my-page.md', <<<'MD'
            ---
            myField: foo
            myNumber: 123
            ---
            
            # My Page
            MD
        );

        $validator = PublicationPageValidator::call($publicationType, 'my-page');
        $validator->validate();

        $this->assertTrue(true);
    }

    public function testValidatePageFileWithInvalidFields()
    {
        $this->directory('test-publication');
        $publicationType = new PublicationType('test-publication', fields: [
            ['name' => 'myField', 'type' => 'string'],
            ['name' => 'myNumber', 'type' => 'integer'],
        ]);
        $publicationType->save();

        $this->file('test-publication/my-page.md', <<<'MD'
            ---
            myField: false
            ---
            
            # My Page
            MD
        );

        $validator = PublicationPageValidator::call($publicationType, 'my-page');

        $this->expectException(ValidationException::class);
        $validator->validate();
    }

    public function testValidatePageFileWithInvalidDataBuffered()
    {
        $this->directory('test-publication');
        $publicationType = new PublicationType('test-publication', fields: [
            ['name' => 'myField', 'type' => 'string'],
            ['name' => 'myNumber', 'type' => 'integer'],
        ]);
        $publicationType->save();

        $this->file('test-publication/my-page.md', <<<'MD'
            ---
            myField: false
            ---
            
            # My Page
            MD
        );

        $validator = PublicationPageValidator::call($publicationType, 'my-page');

        // TODO Fix
        $this->assertSame([
            ['The my field must be a string.'],
            ['The my number must be an integer.'],
        ], $validator->errors());
    }
}
