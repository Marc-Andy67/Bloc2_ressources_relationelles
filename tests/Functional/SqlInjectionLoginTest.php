<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SqlInjectionLoginTest extends WebTestCase
{
    public function test_login_rejects_sql_injection_attempt(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => "admin' OR '1'='1",
            '_password' => "anything' OR '1'='1",
        ]);

        $client->submit($form);

        // Doit rediriger vers /login (échec d'authentification), jamais connecter l'utilisateur
        $this->assertResponseRedirects('/login');
        $client->followRedirect();

        // Un message d'erreur doit être affiché
        $this->assertSelectorExists('.bg-red-50');

        // L'utilisateur ne doit pas être authentifié
        $this->assertSelectorNotExists('a[href*="logout"]');
    }

    public function test_login_rejects_sql_injection_in_password_only(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'admin@example.com',
            '_password' => "' OR '1'='1",
        ]);

        $client->submit($form);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50');
    }
}