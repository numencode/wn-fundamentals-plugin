<?php namespace NumenCode\Fundamentals\Tests;

use Mockery;
use PluginTestCase;

class HelpersTest extends PluginTestCase
{
    /**
     * Test function: numencode_partial
     * Test numencode_partial with a valid partial file and data.
     */
    public function testNumencodePartialWithValidFile(): void
    {
        // Define a mock partial file path
        $partialPath = base_path('plugins/numencode/fundamentals/partials/testPartial.php');

        // Ensure the directory exists
        if (!is_dir(dirname($partialPath))) {
            mkdir(dirname($partialPath), 0777, true);
        }

        // Create a mock partial file
        file_put_contents($partialPath, '<p>Hello, <?= $name ?>!</p>');

        // Call the helper function with data
        $output = numencode_partial('testPartial.php', ['name' => 'World']);

        // Assert the output matches the expected rendered content
        $this->assertEquals('<p>Hello, World!</p>', $output);

        // Clean up the mock partial file
        unlink($partialPath);
    }

    /**
     * Test function: numencode_partial
     * Test numencode_partial with a missing partial file.
     */
    public function testNumencodePartialWithMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Partial file not found:');

        numencode_partial('missingPartial.php');
    }

    /**
     * Test function: numencode_partial
     * Test numencode_partial with partials that return dynamic content.
     */
    public function testNumencodePartialWithDynamicContent(): void
    {
        // Define a mock partial file path
        $partialPath = base_path('plugins/numencode/fundamentals/partials/dynamicPartial.php');

        // Ensure the directory exists
        if (!is_dir(dirname($partialPath))) {
            mkdir(dirname($partialPath), 0777, true);
        }

        // Create a mock partial file with dynamic content
        file_put_contents($partialPath, '<p><?= strtoupper($name) ?> is <?= $age ?> years old.</p>');

        // Call the helper function with data
        $output = numencode_partial('dynamicPartial.php', ['name' => 'Alice', 'age' => 30]);

        // Assert the output matches the expected rendered content
        $this->assertEquals('<p>ALICE is 30 years old.</p>', $output);

        // Clean up the mock partial file
        unlink($partialPath);
    }

    /**
     * Test function: select_options
     * Test the select_options function.
     */
    public function testSelectOptions(): void
    {
        // Define input options
        $options = [
            'value1' => 'Option 1',
            'value2' => 'Option 2',
            'value3' => 'Option 3',
        ];

        // Expected output
        $expected = <<<HTML
<option value="value1">Option 1</option>
<option value="value2">Option 2</option>
<option value="value3">Option 3</option>
HTML;

        // Call the helper function
        $output = select_options($options);

        // Assert that the output matches the expected HTML
        $this->assertEquals($expected, $output);
    }

    /**
     * Test function: select_options
     * Test select_options with an empty array.
     */
    public function testSelectOptionsWithEmptyArray(): void
    {
        // Define input options
        $options = [];

        // Expected output
        $expected = '';

        // Call the helper function
        $output = select_options($options);

        // Assert that the output matches the expected HTML
        $this->assertEquals($expected, $output);
    }

    /**
     * Test function: validate_request
     * Test the validate_request function with valid data.
     */
    public function testValidateRequestWithValidData(): void
    {
        // Mock request data
        $requestData = ['name' => 'John Doe', 'email' => 'john.doe@example.com'];
        request()->merge($requestData);

        // Validation rules
        $rules = [
            'name'  => 'required|string',
            'email' => 'required|email',
        ];

        // Call the helper function
        $isValid = validate_request($rules);

        // Assert the request is valid
        $this->assertTrue($isValid);
        $this->assertNull(session('errors'));
    }

    /**
     * Test function: validate_request
     * Test the validate_request function with invalid data.
     */
    public function testValidateRequestWithInvalidData(): void
    {
        // Mock request data
        $requestData = ['name' => '', 'email' => 'invalid-email'];
        request()->merge($requestData);

        // Validation rules
        $rules = [
            'name'  => 'required|string',
            'email' => 'required|email',
        ];

        // Call the helper function
        $isValid = validate_request($rules);

        // Assert the request is invalid
        $this->assertFalse($isValid);

        // Assert the session contains errors
        $errors = session('errors')->toArray();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    /**
     * Test function: validate_request
     * Test the validate_request function with custom error messages.
     */
    public function testValidateRequestWithCustomMessages(): void
    {
        // Mock request data
        $requestData = ['name' => '', 'email' => 'invalid-email'];
        request()->merge($requestData);

        // Validation rules
        $rules = [
            'name'  => 'required|string',
            'email' => 'required|email',
        ];

        // Custom error messages
        $messages = [
            'name.required' => 'Name is required.',
            'email.email'   => 'Please provide a valid email address.',
        ];

        // Call the helper function
        $isValid = validate_request($rules, $messages);

        // Assert the request is invalid
        $this->assertFalse($isValid);

        // Assert the session contains custom error messages
        $errors = session('errors')->toArray();
        $this->assertEquals('Name is required.', $errors['name'][0]);
        $this->assertEquals('Please provide a valid email address.', $errors['email'][0]);
    }

    /**
     * Test function: validate_request
     * Test the validate_request function with _ajax_validate flag.
     */
    public function testValidateRequestWithAjaxValidate(): void
    {
        // Mock request data with _ajax_validate flag
        $requestData = ['_ajax_validate' => true];
        request()->merge($requestData);

        // Validation rules
        $rules = [
            '_ajax_validate_ensure_failure' => 'required',
        ];

        // Call the helper function
        $isValid = validate_request($rules);

        // Assert the request is invalid due to missing _ajax_validate_ensure_failure
        $this->assertFalse($isValid);

        // Assert the session contains errors for the AJAX validation
        $errors = session('errors')->toArray();
        $this->assertArrayHasKey('_ajax_validate_ensure_failure', $errors);
    }

    /**
     * Test function: array_insert
     * Test inserting data at a specific index.
     */
    public function testArrayInsertByIndex(): void
    {
        $haystack = [1, 2, 3, 4];
        $data = [99];
        $result = array_insert($haystack, 2, $data);

        $this->assertEquals([1, 2, 99, 3, 4], $result, 'Failed to insert by index.');
    }

    /**
     * Test function: array_insert
     * Test inserting data before a specific key.
     */
    public function testArrayInsertByKey(): void
    {
        $haystack = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
        $data = ['z' => 99];
        $result = array_insert($haystack, 'c', $data);

        $this->assertEquals(
            ['a' => 1, 'b' => 2, 'z' => 99, 'c' => 3, 'd' => 4],
            $result,
            'Failed to insert by key.'
        );
    }

    /**
     * Test function: array_insert
     * Test inserting data at the beginning of the array.
     */
    public function testArrayInsertAtStart(): void
    {
        $haystack = [1, 2, 3, 4];
        $data = [0];
        $result = array_insert($haystack, 0, $data);

        $this->assertEquals([0, 1, 2, 3, 4], $result, 'Failed to insert at the start.');
    }

    /**
     * Test function: array_insert
     * Test inserting data at the end of the array.
     */
    public function testArrayInsertAtEnd(): void
    {
        $haystack = [1, 2, 3];
        $data = [99];
        $result = array_insert($haystack, 3, $data);

        $this->assertEquals([1, 2, 3, 99], $result, 'Failed to insert at the end.');
    }

    /**
     * Test function: array_insert
     * Test inserting data when the key is not found.
     */
    public function testArrayInsertWithNonExistentKey(): void
    {
        $haystack = ['a' => 1, 'b' => 2, 'c' => 3];
        $data = ['z' => 99];
        $result = array_insert($haystack, 'x', $data);

        // Default behavior: appends to the end if the key doesn't exist
        $this->assertEquals(
            ['a' => 1, 'b' => 2, 'c' => 3, 'z' => 99],
            $result,
            'Failed to handle non-existent key.'
        );
    }

    /**
     * Test function: array_before
     * Test array_before with valid data where the element should be moved.
     */
    public function testArrayBeforeWithValidData(): void
    {
        $haystack = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $expected = [
            'a' => 'A',
            'c' => 'C',
            'b' => 'B'
        ];

        $result = array_before($haystack, 'b', 'c');

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_before
     * Test array_before when the needle is not found.
     */
    public function testArrayBeforeWithNeedleNotFound(): void
    {
        $haystack = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $result = array_before($haystack, 'x', 'b');

        // Assert the result is the same as the original array
        $this->assertEquals($haystack, $result);
    }

    /**
     * Test function: array_before
     * Test array_before when the element to move is not found.
     */
    public function testArrayBeforeWithMoveNotFound(): void
    {
        $haystack = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $result = array_before($haystack, 'b', 'x');

        // Assert the result is the same as the original array
        $this->assertEquals($haystack, $result);
    }

    /**
     * Test function: array_before
     * Test array_before with the element moved before the first element.
     */
    public function testArrayBeforeWithMoveBeforeFirstElement(): void
    {
        $haystack = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $expected = [
            'b' => 'B',
            'a' => 'A',
            'c' => 'C'
        ];

        $result = array_before($haystack, 'a', 'b');

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_before
     * Test array_before with the element moved before the last element.
     */
    public function testArrayBeforeWithMoveBeforeLastElement(): void
    {
        $haystack = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $expected = [
            'a' => 'A',
            'c' => 'C',
            'b' => 'B'
        ];

        $result = array_before($haystack, 'b', 'c');

        // Assert the result matches the expected output
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_before
     * Test array_before with an empty array.
     */
    public function testArrayBeforeWithEmptyArray(): void
    {
        $haystack = [];

        $result = array_before($haystack, 'a', 'b');

        // Assert the result is the same as the original empty array
        $this->assertEquals($haystack, $result);
    }

    /**
     * Test function: array_before
     * Test array_before with an array containing one element.
     */
    public function testArrayBeforeWithOneElement(): void
    {
        $haystack = ['a' => 'A'];

        $result = array_before($haystack, 'a', 'a');

        // Assert the result is the same as the original array
        $this->assertEquals($haystack, $result);
    }

    /**
     * Test function: array_merge_reference
     * Test merging two arrays with no conflicts.
     */
    public function testArrayMergeReferenceWithoutConflict(): void
    {
        $array1 = ['a' => 'A', 'b' => 'B'];
        $array2 = ['c' => 'C', 'd' => 'D'];

        $expected = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C',
            'd' => 'D'
        ];

        $result = array_merge_reference($array1, $array2);

        // Assert the result is the merged array
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_merge_reference
     * Test merging arrays with overlapping keys.
     */
    public function testArrayMergeReferenceWithConflict(): void
    {
        $array1 = ['a' => 'A', 'b' => 'B'];
        $array2 = ['b' => 'C', 'c' => 'D'];

        $expected = [
            'a' => 'A',
            'b' => 'C',  // 'b' from $array2 overwrites 'b' from $array1
            'c' => 'D'
        ];

        $result = array_merge_reference($array1, $array2);

        // Assert the result is the merged array with conflict resolution
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_merge_reference
     * Test merging arrays with numeric keys.
     */
    public function testArrayMergeReferenceWithNumericKeys(): void
    {
        $array1 = [1 => 'A', 2 => 'B'];
        $array2 = [3 => 'C', 4 => 'D'];

        $expected = [
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D'
        ];

        $result = array_merge_reference($array1, $array2);

        // Assert the result is the merged array with numeric keys
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_merge_reference
     * Test merging with an empty array.
     */
    public function testArrayMergeReferenceWithEmptyArray(): void
    {
        $array1 = ['a' => 'A'];
        $array2 = [];

        $expected = [
            'a' => 'A'
        ];

        $result = array_merge_reference($array1, $array2);

        // Assert the result matches the original array since the second array is empty
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_merge_reference
     * Test merging multiple arrays.
     */
    public function testArrayMergeReferenceWithMultipleArrays(): void
    {
        $array1 = ['a' => 'A'];
        $array2 = ['b' => 'B'];
        $array3 = ['c' => 'C'];

        $expected = [
            'a' => 'A',
            'b' => 'B',
            'c' => 'C'
        ];

        $result = array_merge_reference($array1, $array2, $array3);

        // Assert the result is the merged array with all arrays combined
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_search_recursive
     * Test finding a value in a flat array.
     */
    public function testArraySearchRecursiveFlatArray(): void
    {
        $haystack = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

        $expected = ['b'];
        $result = array_search_recursive('B', $haystack);

        // Assert the result is the correct key for the value 'B'
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_search_recursive
     * Test finding a value in a multi-dimensional array.
     */
    public function testArraySearchRecursiveMultiDimensionalArray(): void
    {
        $haystack = [
            'a' => ['x' => 'X', 'y' => 'Y'],
            'b' => ['z' => 'Z'],
            'c' => 'C'
        ];

        $expected = ['a', 'y'];  // The value 'Y' is found under 'a' -> 'y'
        $result = array_search_recursive('Y', $haystack);

        // Assert the result matches the expected key path
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_search_recursive
     * Test when the value is not found.
     */
    public function testArraySearchRecursiveValueNotFound(): void
    {
        $haystack = ['a' => 'A', 'b' => 'B', 'c' => 'C'];

        $result = array_search_recursive('D', $haystack);

        // Assert the result is an empty array since 'D' is not found
        $this->assertEmpty($result);
    }

    /**
     * Test function: array_search_recursive
     * Test searching for a value inside a deeply nested array.
     */
    public function testArraySearchRecursiveDeeplyNestedArray(): void
    {
        $haystack = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 'D'
                    ]
                ]
            ]
        ];

        $expected = ['a', 'b', 'c', 'd'];  // The value 'D' is found under the path 'a' -> 'b' -> 'c' -> 'd'
        $result = array_search_recursive('D', $haystack);

        // Assert the result matches the expected key path
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_search_recursive
     * Test searching for a value when keys are numeric.
     */
    public function testArraySearchRecursiveNumericKeys(): void
    {
        $haystack = [
            0 => ['a' => 'A'],
            1 => ['b' => 'B'],
            2 => 'C'
        ];

        $expected = [1, 'b'];  // The value 'B' is found under the path 1 -> 'b'
        $result = array_search_recursive('B', $haystack);

        // Assert the result matches the expected key path
        $this->assertEquals($expected, $result);
    }

    /**
     * Test function: array_search_recursive
     * Test searching in an empty array.
     */
    public function testArraySearchRecursiveEmptyArray(): void
    {
        $haystack = [];

        $result = array_search_recursive('A', $haystack);

        // Assert the result is an empty array since the array is empty
        $this->assertEmpty($result);
    }

    /**
     * Test function: round_global
     * Test rounding a number with default decimal places (2).
     */
    public function testRoundGlobalWithDefaultDecimals(): void
    {
        // Set default round_decimals to 2 for the test (if not set in config).
        config(['app.round_decimals' => 2]);

        $result = round_global(12.3456);

        // Assert the result is rounded to 2 decimal places
        $this->assertEquals(12.35, $result);
    }

    /**
     * Test function: round_global
     * Test rounding a number with custom decimal places.
     */
    public function testRoundGlobalWithCustomDecimals(): void
    {
        // Set round_decimals to 3 for the test.
        config(['app.round_decimals' => 3]);

        $result = round_global(12.3456);

        // Assert the result is rounded to 3 decimal places
        $this->assertEquals(12.346, $result);
    }

    /**
     * Test function: round_global
     * Test rounding a number with non-decimal input.
     */
    public function testRoundGlobalWithNonDecimalInput(): void
    {
        // Set default round_decimals to 2 for the test.
        config(['app.round_decimals' => 2]);

        $result = round_global(12);

        // Assert the result is rounded correctly (no change expected)
        $this->assertEquals(12.00, $result);
    }

    /**
     * Test function: round_global
     * Test rounding a number with a negative value.
     */
    public function testRoundGlobalWithNegativeValue(): void
    {
        // Set default round_decimals to 2 for the test.
        config(['app.round_decimals' => 2]);

        $result = round_global(-12.3456);

        // Assert the result is rounded to 2 decimal places
        $this->assertEquals(-12.35, $result);
    }

    /**
     * Test function: round_global
     * Test rounding a string numeric value (PHP will convert it to a number).
     */
    public function testRoundGlobalWithStringNumericValue(): void
    {
        // Set default round_decimals to 2 for the test.
        config(['app.round_decimals' => 2]);

        $result = round_global('12.3456');

        // Assert the result is rounded correctly
        $this->assertEquals(12.35, $result);
    }

    /**
     * Test function: round_global
     * Test rounding with no configuration set (fallback to 2 decimal places).
     */
    public function testRoundGlobalWithNoConfig(): void
    {
        // Ensure the configuration does not have a round_decimals value
        config(['app.round_decimals' => null]);

        $result = round_global(12.3456);

        // Assert that the result is rounded to 2 decimal places as the default
        $this->assertEquals(12.35, $result);
    }
}
