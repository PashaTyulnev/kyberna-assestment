<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoanApiTest extends WebTestCase
{
    public function testGetCollectionReturnsArray(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/loans', server: ['HTTP_ACCEPT' => 'application/ld+json']);

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($data['member'] ?? null);
    }
}
