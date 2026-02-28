<?php

namespace App\Tests\Controller;

use App\Entity\ChatMessage;
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
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->chatMessageRepository = $this->manager->getRepository(ChatMessage::class);

        foreach ($this->chatMessageRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatMessage index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'chat_message[Content]' => 'Testing',
            'chat_message[creationDate]' => 'Testing',
            'chat_message[chatRoom]' => 'Testing',
            'chat_message[author]' => 'Testing',
        ]);

        self::assertResponseRedirects('/chat/message');

        self::assertSame(1, $this->chatMessageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $user = new \App\Entity\User();
        $user->setEmail('test' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);

        $category = new \App\Entity\Category();
        $category->setName('Test Category');
        $this->manager->persist($category);

        $ressource = new \App\Entity\Ressource();
        $ressource->setTitle('My Title');
        $ressource->setContent('My Title');
        $ressource->setType('My Title');
        $ressource->setCreationDate(new \DateTime());
        $ressource->setStatus(true);
        $ressource->setAuthor($user);
        $ressource->setCategory($category);
        $this->manager->persist($ressource);

        $chatRoom = new \App\Entity\ChatRoom();
        $chatRoom->setName('My Room');
        $chatRoom->setRessource($ressource);
        $this->manager->persist($chatRoom);

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

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $user = new \App\Entity\User();
        $user->setEmail('test' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);

        $category = new \App\Entity\Category();
        $category->setName('Test Category');
        $this->manager->persist($category);

        $ressource = new \App\Entity\Ressource();
        $ressource->setTitle('Value');
        $ressource->setContent('Value');
        $ressource->setType('Value');
        $ressource->setCreationDate(new \DateTime());
        $ressource->setStatus(true);
        $ressource->setAuthor($user);
        $ressource->setCategory($category);
        $this->manager->persist($ressource);

        $chatRoom = new \App\Entity\ChatRoom();
        $chatRoom->setName('My Room');
        $chatRoom->setRessource($ressource);
        $this->manager->persist($chatRoom);

        $fixture = new ChatMessage();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setChatRoom($chatRoom);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'chat_message[Content]' => 'Something New',
            'chat_message[creationDate]' => 'Something New',
            'chat_message[chatRoom]' => 'Something New',
            'chat_message[author]' => 'Something New',
        ]);

        self::assertResponseRedirects('/chat/message');

        $fixture = $this->chatMessageRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getContent());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $user = new \App\Entity\User();
        $user->setEmail('test' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);

        $category = new \App\Entity\Category();
        $category->setName('Test Category');
        $this->manager->persist($category);

        $ressource = new \App\Entity\Ressource();
        $ressource->setTitle('Value');
        $ressource->setContent('Value');
        $ressource->setType('Value');
        $ressource->setCreationDate(new \DateTime());
        $ressource->setStatus(true);
        $ressource->setAuthor($user);
        $ressource->setCategory($category);
        $this->manager->persist($ressource);

        $chatRoom = new \App\Entity\ChatRoom();
        $chatRoom->setName('My Room');
        $chatRoom->setRessource($ressource);
        $this->manager->persist($chatRoom);

        $fixture = new ChatMessage();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setChatRoom($chatRoom);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/chat/message');
        self::assertSame(0, $this->chatMessageRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
