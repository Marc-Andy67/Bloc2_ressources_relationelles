<?php

namespace App\Tests\Controller;

use App\Entity\ChatRoom;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ChatRoomControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $chatRoomRepository;
    private string $path = '/chat/room/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->chatRoomRepository = $this->manager->getRepository(ChatRoom::class);

        foreach ($this->chatRoomRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatRoom index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $user = new \App\Entity\User();
        $user->setEmail('testnew' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);

        $category = new \App\Entity\Category();
        $category->setName('Test Category');
        $this->manager->persist($category);

        $ressource = new \App\Entity\Ressource();
        $ressource->setTitle('My Title');
        $ressource->setContent('My Title');
        $ressource->setType('My Title');
        $ressource->setStatus(true);
        $ressource->setAuthor($user);
        $ressource->setCategory($category);
        $this->manager->persist($ressource);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'chat_room[name]' => 'Testing room',
            'chat_room[Ressource]' => $ressource->getId(),
            'chat_room[members]' => [$user->getId()],
        ]);

        self::assertResponseRedirects('/chat/room');

        self::assertSame(1, $this->chatRoomRepository->count([]));

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

        $fixture = new ChatRoom();
        $fixture->setName('My Title');
        $fixture->setRessource($ressource);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatRoom');

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

        $fixture = new ChatRoom();
        $fixture->setName('Value');
        $fixture->setRessource($ressource);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'chat_room[name]' => 'Something New Room',
            'chat_room[Ressource]' => $ressource->getId(),
            'chat_room[members]' => [$user->getId()],
        ]);

        self::assertResponseRedirects('/chat/room');

        $fixture = $this->chatRoomRepository->findAll();

        self::assertSame('Something New Room', $fixture[0]->getName());

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

        $fixture = new ChatRoom();
        $fixture->setName('Value');
        $fixture->setRessource($ressource);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/chat/room');
        self::assertSame(0, $this->chatRoomRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
