<?php

namespace App\Tests\Controller;

use App\Entity\ChatMessage;
use App\Entity\ChatRoom;
use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ChatMessageControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $chatMessageRepository;
    private string $path = '/chat/message/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->manager = $manager;
        $this->chatMessageRepository = $this->manager->getRepository(ChatMessage::class);

        foreach ($this->chatMessageRepository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    private function createUserAndRoom(): array
    {
        $user = new User();
        $user->setEmail('test' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);

        $category = new Category();
        $category->setName('Test Category');
        $this->manager->persist($category);

        $ressource = new Ressource();
        $ressource->setTitle('My Title');
        $ressource->setContent('My Content');
        $ressource->setType('article');
        $ressource->setCreationDate(new \DateTime());
        $ressource->setStatus('pending');
        $ressource->setAuthor($user);
        $ressource->setCategory($category);
        $this->manager->persist($ressource);

        $chatRoom = new ChatRoom();
        $chatRoom->setName('Test Room');
        $chatRoom->setRessource($ressource);
        $this->manager->persist($chatRoom);

        $this->manager->flush();

        return [$user, $chatRoom];
    }

    public function testIndex(): void
    {
        [$user] = $this->createUserAndRoom();
        $this->client->loginUser($user);

        // Use canonical URL without trailing slash to avoid 301
        $this->client->request('GET', '/chat/message');

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatMessage index');
    }

    public function testShow(): void
    {
        [$user, $chatRoom] = $this->createUserAndRoom();
        $this->client->loginUser($user);

        $fixture = new ChatMessage();
        $fixture->setContent('My Title');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setChatRoom($chatRoom);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatMessage');
    }

    public function testEdit(): void
    {
        [$user, $chatRoom] = $this->createUserAndRoom();
        $this->client->loginUser($user);

        $fixture = new ChatMessage();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setChatRoom($chatRoom);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier les labels des boutons du template chat_message/edit.html.twig');
    }

    public function testRemove(): void
    {
        [$user, $chatRoom] = $this->createUserAndRoom();
        $this->client->loginUser($user);

        $fixture = new ChatMessage();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setChatRoom($chatRoom);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier les labels des boutons du template chat_message/show.html.twig');
    }
}