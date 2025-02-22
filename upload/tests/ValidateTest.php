<?php

declare(strict_types=1);

namespace App\Tests;

use App\Model\User;

class ValidateTest extends ApiTestCase
{
    public function testValidateOK(): void
    {
        $response = $this->request(User::ADMIN_USER, 'POST', '/form/validate', [
            'data' => [
                'album' => 'Foo',
                'agreed' => true,
            ],
        ]);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['errors' => []], $json);
    }

    public function testValidateWithAnonymousUser(): void
    {
        $response = $this->request(null, 'POST', '/form/validate', [
            'data' => [
                'album' => 'Foo',
                'agreed' => true,
            ],
        ]);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * @dataProvider formDataProvider
     */
    public function testValidateGivesErrors(array $data, array $exceptedErrors): void
    {
        $response = $this->request(User::ADMIN_USER, 'POST', '/form/validate', [
            'data' => $data,
        ]);
        $json = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['errors' => $exceptedErrors], $json);
    }

    public function formDataProvider(): array
    {
        return [
            [[
                'album' => 'Foo',
                'agreed' => true,
            ], []],

            [[
                'album' => '',
                'agreed' => true,
            ], [
                'album' => ['This value should not be blank.'],
            ]],

            [[
                'album' => '',
                'agreed' => false,
            ], [
                'album' => ['This value should not be blank.'],
                'agreed' => ['This value should not be blank.'],
            ]],

            [[
            ], [
                'album' => ['This value should not be blank.'],
                'agreed' => ['This value should not be blank.'],
            ]],
        ];
    }
}
