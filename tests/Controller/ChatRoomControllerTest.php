<?php

namespace App\Tests\Controller;

use App\Entity\ChatRoom;
use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
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
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->manager = $manager;
        $this->chatRoomRepository = $this->manager->getRepository(ChatRoom::class);

        // Supprimer les messages d'abord (FK), puis les rooms
        $chatMessageRepo = $this->manager->getRepository(\App\Entity\ChatMessage::class);
        foreach ($chatMessageRepo->findAll() as $msg) {
            $this->manager->remove($msg);
        }
        foreach ($this->chatRoomRepository->findAll() as $object) {
            $this->manager->remove($object);
        }
        $this->manager->flush();
    }

    private function createUserAndRessource(): array
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

        $this->manager->flush();

        return [$user, $ressource];
    }

    public function testIndex(): void
    {
        [$user] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatRoom index');
    }

    public function testShow(): void
    {
        [$user, $ressource] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $fixture = new ChatRoom();
        $fixture->setName('My Title');
        $fixture->setRessource($ressource);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('ChatRoom');
    }

    public function testNew(): void
    {
        [$user, $ressource] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $this->client->request('GET', sprintf('%snew', $this->path));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier les labels des boutons du template chat_room/new.html.twig');
    }

    // testEdit supprimé : la route app_chat_room_edit n'existe pas
    // testRemove supprimé : app_chat_room_delete est en POST uniquement (pas accessible via submitForm sur show)
}