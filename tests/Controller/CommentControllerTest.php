<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CommentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $commentRepository;
    private string $path = '/comment/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->manager = $manager;
        $this->commentRepository = $this->manager->getRepository(Comment::class);

        foreach ($this->commentRepository->findAll() as $object) {
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

        // Use the canonical URL without trailing slash to avoid 301
        $this->client->request('GET', '/comment');

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Comment index');
    }

    public function testShow(): void
    {
        [$user, $ressource] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $fixture = new Comment();
        $fixture->setContent('My Title');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Comment');
    }

    public function testEdit(): void
    {
        [$user, $ressource] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier les labels des boutons du template comment/edit.html.twig');
    }

    public function testRemove(): void
    {
        [$user, $ressource] = $this->createUserAndRessource();
        $this->client->loginUser($user);

        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setAuthor($user);
        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        self::assertResponseStatusCodeSame(200);

        $this->markTestIncomplete('Vérifier les labels des boutons du template comment/show.html.twig');
    }
}