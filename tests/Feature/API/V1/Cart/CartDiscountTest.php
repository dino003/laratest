<?php

namespace Tests\Feature\API\V1\Cart;

use Tests\TestCase;

/**
 * /cart/discount.
 */
class CartDiscountTest extends TestCase
{
    /**
     * @dataProvider getCartDiscountsFixtures
     */
    public function testCartDiscountRoute(array $data, int $statusCode, array $responseBody)
    {
        // Actions
        $response = $this->postJson('/api/v1/cart/discount', $data);
        $content = json_decode($response->getContent(), true);

        // Assertions
        $response->assertStatus($statusCode);
        $this->assertEquals($responseBody, $content, 'The discount calculation is wrong.');
    }

    public function getCartDiscountsFixtures()
    {
        // These tests are passing
        yield from $this->getFixture('invalid_negative_product_values');
        yield from $this->getFixture('invalid_product_fields_format');
        yield from $this->getFixture('invalid_user_email_and_products_format');
        yield from $this->getFixture('invalid_with_same_product_twice');
        yield from $this->getFixture('invalid_without_product_fields');
        yield from $this->getFixture('invalid_without_user_email_and_products');

        // These ones you need to make pass.
        // Uncomment the lines, one by one, so that it's easier to implement :)

        // yield from $this->getFixture('valid_with_no_discount');
        // yield from $this->getFixture('valid_with_no_discount_for_other_category');
        // yield from $this->getFixture('valid_with_no_discount_for_other_products');
        // yield from $this->getFixture('valid_with_no_discount_for_new_customer_when_below_50');

        // yield from $this->getFixture('valid_with_discount_above_3000');
        // yield from $this->getFixture('valid_with_discount_take_3_pay_2');
//        yield from $this->getFixture('valid_with_discount_take_3_pay_2_multiple_times');
        yield from $this->getFixture('valid_with_discount_for_same_category');
        yield from $this->getFixture('valid_with_discount_for_same_category_same_price');
//        yield from $this->getFixture('valid_with_discount_for_employee');
//        yield from $this->getFixture('valid_with_discount_for_new_customer');
//        yield from $this->getFixture('valid_with_only_the_biggest_discount');
    }

    private function getFixture(string $name): array
    {
        $fixtureArray = json_decode(
            file_get_contents(__DIR__."/fixtures/{$name}.json") ?: '',
            true
        );

        return [
            "fixtures/{$name}.json" => [
                'data' => $fixtureArray['request']['body'],
                'statusCode' => $fixtureArray['response']['statusCode'],
                'responseBody' => $fixtureArray['response']['body'],
            ],
        ];
    }
}
