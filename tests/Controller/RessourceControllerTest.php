<?php

namespace App\Tests\Controller;

use App\Entity\Ressource;
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
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->ressourceRepository = $this->manager->getRepository(Ressource::class);

        foreach ($this->ressourceRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Ressource index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'ressource[title]' => 'Testing',
            'ressource[content]' => 'Testing',
            'ressource[type]' => 'Testing',
            'ressource[creationDate]' => 'Testing',
            'ressource[status]' => 'Testing',
            'ressource[size]' => 'Testing',
            'ressource[category]' => 'Testing',
            'ressource[author]' => 'Testing',
            'ressource[relationTypes]' => 'Testing',
            'ressource[favoritedBy]' => 'Testing',
            'ressource[setAsideBy]' => 'Testing',
            'ressource[LikedBy]' => 'Testing',
        ]);

        self::assertResponseRedirects('/ressource');

        self::assertSame(1, $this->ressourceRepository->count([]));

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

        $fixture = new Ressource();
        $fixture->setTitle('My Title');
        $fixture->setContent('My Title');
        $fixture->setType('My Title');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus(true);
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Ressource');

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

        $fixture = new Ressource();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $fixture->setType('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus(true);
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'ressource[title]' => 'Something New',
            'ressource[content]' => 'Something New',
            'ressource[type]' => 'Something New',
            'ressource[creationDate]' => 'Something New',
            'ressource[status]' => 'Something New',
            'ressource[size]' => 'Something New',
            'ressource[category]' => 'Something New',
            'ressource[author]' => 'Something New',
            'ressource[relationTypes]' => 'Something New',
            'ressource[favoritedBy]' => 'Something New',
            'ressource[setAsideBy]' => 'Something New',
            'ressource[LikedBy]' => 'Something New',
        ]);

        self::assertResponseRedirects('/ressource');

        $fixture = $this->ressourceRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getContent());
        self::assertSame('Something New', $fixture[0]->getType());

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

        $fixture = new Ressource();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $fixture->setType('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setStatus(true);
        $fixture->setSize(10);
        $fixture->setCategory($category);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/ressource');
        self::assertSame(0, $this->ressourceRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
