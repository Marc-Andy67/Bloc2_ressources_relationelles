<?php

namespace App\Tests;

use App\Entity\ChatMessage;
use App\Entity\ChatRoom;
use App\Entity\Comment;
use App\Entity\Progression;
use App\Entity\Ressource;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = static::getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        // Delete FK-dependant entities before users
        foreach ($em->getRepository(ChatMessage::class)->findAll() as $o) {
            $em->remove($o);
        }
        foreach ($em->getRepository(ChatRoom::class)->findAll() as $o) {
            $em->remove($o);
        }
        foreach ($em->getRepository(Comment::class)->findAll() as $o) {
            $em->remove($o);
        }
        foreach ($em->getRepository(Progression::class)->findAll() as $o) {
            $em->remove($o);
        }
        foreach ($em->getRepository(Ressource::class)->findAll() as $o) {
            $em->remove($o);
        }
        foreach ($this->userRepository->findAll() as $user) {
            $em->remove($user);
        }

        $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains("S'inscrire");

        $this->client->submitForm('Créer mon compte', [
            'registration_form[email]' => 'me@example.com',
            'registration_form[name]' => 'John Doe',
            'registration_form[plainPassword]' => 'Valid@12345678',
            'registration_form[agreeTerms]' => true,
        ]);

        // Ensure the response redirects after submitting the form, the user exists, and is not verified
        // self::assertResponseRedirects('/'); @TODO: set the appropriate path that the user is redirected to.
        self::assertCount(1, $this->userRepository->findAll());
    }
}