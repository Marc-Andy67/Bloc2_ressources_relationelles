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
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'chat_room[name]' => 'Testing',
            'chat_room[Ressource]' => 'Testing',
            'chat_room[members]' => 'Testing',
        ]);

        self::assertResponseRedirects('/chat/room');

        self::assertSame(1, $this->chatRoomRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new ChatRoom();
        $fixture->setName('My Title');
        $fixture->setRessource('My Title');
        $fixture->setMembers('My Title');

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
        $fixture = new ChatRoom();
        $fixture->setName('Value');
        $fixture->setRessource('Value');
        $fixture->setMembers('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'chat_room[name]' => 'Something New',
            'chat_room[Ressource]' => 'Something New',
            'chat_room[members]' => 'Something New',
        ]);

        self::assertResponseRedirects('/chat/room');

        $fixture = $this->chatRoomRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getRessource());
        self::assertSame('Something New', $fixture[0]->getMembers());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new ChatRoom();
        $fixture->setName('Value');
        $fixture->setRessource('Value');
        $fixture->setMembers('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/chat/room');
        self::assertSame(0, $this->chatRoomRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
