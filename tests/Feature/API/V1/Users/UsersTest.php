<?php
namespace Tests\Feature\API\V1\Users;

use Tests\TestCase;

/**
 * /user/{email}
 */
class UsersTest extends TestCase
{
    public function testUserShouldBeAnEmployee()
    {
        // Set
        $email = 'boitata@boitata.com';

        // Actions
        $response = $this->getJson("/api/v1/user/{$email}");

        // Assertions
        $response->assertOk();
        $response->assertExactJson(
            [
                'message' => 'Success.',
                'data' => [
                    'email' => $email,
                    'isEmployee' => true,
                ],
            ]
        );
    }

    public function testUserShouldNotBeAnEmployee()
    {
        // Set
        $email = 'johndoe@pm.me';

        // Actions
        $response = $this->getJson("/api/v1/user/{$email}");

        // Assertions
        $response->assertOk();
        $response->assertExactJson(
            [
                'message' => 'Success.',
                'data' => [
                    'email' => $email,
                    'isEmployee' => false,
                ],
            ]
        );
    }

    public function testUserShouldNotExist()
    {
        // Set
        $email = 'newuser@email.com';

        // Actions
        $response = $this->getJson("/api/v1/user/{$email}");

        // Assertions
        $response->assertStatus(404);
        $response->assertJson(
            [
                'message' => 'Not Found.',
            ]
        );
    }

    public function testIdShouldBeAnEmail()
    {
        // Set
        $email = 'non-an-email';

        // Actions
        $response = $this->getJson("/api/v1/user/{$email}");

        // Assertions
        $response->assertStatus(422);
        $response->assertJson(
            [
                'message' => 'The given data was invalid.',
                'errors' => [
                    'email' => [
                        'The email must be a valid email address.',
                    ],
                ],
            ]
        );
    }
}
