<?php
namespace App\Http\Requests;

use Tests\TestCase;

class CartDiscountRequestTest extends TestCase
{
    public function testShouldAuthorizeAnyRequest()
    {
        // Set
        $formRequest = CartDiscountRequest::create('/');

        // Actions
        $result = $formRequest->authorize();

        // Assertions
        $this->assertTrue($result);
    }

    public function testRules()
    {
        // Set
        $formRequest = CartDiscountRequest::create('/');

        // Actions
        $result = $formRequest->rules();

        // Assertions
        $this->assertNotEmpty($result);
    }
}
