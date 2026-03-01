<?php

namespace App\Tests\Controller;

use App\Entity\Progression;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProgressionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $progressionRepository;
    private string $path = '/progression/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->progressionRepository = $this->manager->getRepository(Progression::class);

        foreach ($this->progressionRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Progression index');

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
            'progression[description]' => 'Testing progress',
            'progression[date]' => '2023-01-01',
            'progression[ressource]' => $ressource->getId(),
            'progression[user]' => $user->getId(),
        ]);

        self::assertResponseRedirects('/progression');

        self::assertSame(1, $this->progressionRepository->count([]));

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

        $fixture = new Progression();
        $fixture->setDescription('My Title');
        $fixture->setDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setUser($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Progression');

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

        $fixture = new Progression();
        $fixture->setDescription('Value');
        $fixture->setDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setUser($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'progression[description]' => 'Something New progress',
            'progression[date]' => '2023-01-01',
            'progression[ressource]' => $ressource->getId(),
            'progression[user]' => $user->getId(),
        ]);

        self::assertResponseRedirects('/progression');

        $fixture = $this->progressionRepository->findAll();

        self::assertSame('Something New progress', $fixture[0]->getDescription());

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

        $fixture = new Progression();
        $fixture->setDescription('Value');
        $fixture->setDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setUser($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/progression');
        self::assertSame(0, $this->progressionRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
