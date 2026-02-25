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
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'progression[description]' => 'Testing',
            'progression[date]' => 'Testing',
            'progression[ressource]' => 'Testing',
            'progression[user]' => 'Testing',
        ]);

        self::assertResponseRedirects('/progression');

        self::assertSame(1, $this->progressionRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Progression();
        $fixture->setDescription('My Title');
        $fixture->setDate('My Title');
        $fixture->setRessource('My Title');
        $fixture->setUser('My Title');

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
        $fixture = new Progression();
        $fixture->setDescription('Value');
        $fixture->setDate('Value');
        $fixture->setRessource('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'progression[description]' => 'Something New',
            'progression[date]' => 'Something New',
            'progression[ressource]' => 'Something New',
            'progression[user]' => 'Something New',
        ]);

        self::assertResponseRedirects('/progression');

        $fixture = $this->progressionRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getDate());
        self::assertSame('Something New', $fixture[0]->getRessource());
        self::assertSame('Something New', $fixture[0]->getUser());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Progression();
        $fixture->setDescription('Value');
        $fixture->setDate('Value');
        $fixture->setRessource('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/progression');
        self::assertSame(0, $this->progressionRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
