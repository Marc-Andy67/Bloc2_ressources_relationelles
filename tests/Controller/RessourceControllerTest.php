<?php

namespace App\Tests\Controller;

use App\Entity\Ressource;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\ChatRoom;
use App\Entity\ChatMessage;
use App\Entity\Comment;
use App\Entity\Progression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RessourceControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $ressourceRepository;
    private string $path = '/ressource/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->manager = $manager;
        $this->ressourceRepository = $this->manager->getRepository(Ressource::class);

        // Ordre de suppression respectant les FK :
        // 1. ChatMessage (référence ChatRoom)
        // 2. ChatRoom (référence Ressource)
        // 3. Comment (référence Ressource)
        // 4. Progression (référence Ressource, onDelete CASCADE mais on nettoie quand même)
        // 5. Ressource
        foreach ($this->manager->getRepository(ChatMessage::class)->findAll() as $o) {
            $this->manager->remove($o);
        }
        foreach ($this->manager->getRepository(ChatRoom::class)->findAll() as $o) {
            $this->manager->remove($o);
        }
        foreach ($this->manager->getRepository(Comment::class)->findAll() as $o) {
            $this->manager->remove($o);
        }
        foreach ($this->manager->getRepository(Progression::class)->findAll() as $o) {
            $this->manager->remove($o);
        }
        foreach ($this->ressourceRepository->findAll() as $o) {
            $this->manager->remove($o);
        }
        $this->manager->flush();
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test' . uniqid() . '@test.com');
        $user->setPassword('password');
        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    private function createCategory(): Category
    {
        $category = new Category();
        $category->setName('Test Category');
        $this->manager->persist($category);
        $this->manager->flush();

        return $category;
    }

    public function testIndex(): void
    {
        $user = $this->createUser();
        $this->client->loginUser($user);

        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Explorer les Ressources');
    }

    public function testNew(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();
        $this->client->loginUser($user);

        $this->client->request('GET', sprintf('%snew', $this->path));
        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Publier la ressource', [
            'ressource[title]'    => 'Testing',
            'ressource[content]'  => 'Testing',
            'ressource[category]' => $category->getId(),
        ]);

        self::assertResponseRedirects('/ressource');
        self::assertSame(1, $this->ressourceRepository->count([]));
    }

    public function testShow(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();

        $fixture = new Ressource();
        $fixture->setTitle('My Title');
        $fixture->setContent('My Content');
        $fixture->setType('article');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus('pending');
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Ressource');
    }

    public function testEdit(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();

        $fixture = new Ressource();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $fixture->setType('article');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus('pending');
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Mettre à jour', [
            'ressource[title]'    => 'Something New',
            'ressource[content]'  => 'Something New',
            'ressource[category]' => $category->getId(),
        ]);

        self::assertResponseRedirects('/ressource');

        $this->manager->clear();
        $fixtureItems = $this->ressourceRepository->findAll();

        self::assertSame('Something New', $fixtureItems[0]->getTitle());
        self::assertSame('Something New', $fixtureItems[0]->getContent());
    }

    public function testRemove(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory();

        $fixture = new Ressource();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $fixture->setType('article');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus('pending');
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier le label du bouton Delete dans ressource/show.html.twig');
    }
}