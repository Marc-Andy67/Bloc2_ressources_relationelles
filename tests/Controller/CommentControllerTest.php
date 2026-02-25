<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
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
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->commentRepository = $this->manager->getRepository(Comment::class);

        foreach ($this->commentRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Comment index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'comment[content]' => 'Testing',
            'comment[creationDate]' => 'Testing',
            'comment[parent]' => 'Testing',
            'comment[ressource]' => 'Testing',
            'comment[author]' => 'Testing',
        ]);

        self::assertResponseRedirects('/comment');

        self::assertSame(1, $this->commentRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Comment();
        $fixture->setContent('My Title');
        $fixture->setCreationDate('My Title');
        $fixture->setParent('My Title');
        $fixture->setRessource('My Title');
        $fixture->setAuthor('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Comment');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate('Value');
        $fixture->setParent('Value');
        $fixture->setRessource('Value');
        $fixture->setAuthor('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'comment[content]' => 'Something New',
            'comment[creationDate]' => 'Something New',
            'comment[parent]' => 'Something New',
            'comment[ressource]' => 'Something New',
            'comment[author]' => 'Something New',
        ]);

        self::assertResponseRedirects('/comment');

        $fixture = $this->commentRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getContent());
        self::assertSame('Something New', $fixture[0]->getCreationDate());
        self::assertSame('Something New', $fixture[0]->getParent());
        self::assertSame('Something New', $fixture[0]->getRessource());
        self::assertSame('Something New', $fixture[0]->getAuthor());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate('Value');
        $fixture->setParent('Value');
        $fixture->setRessource('Value');
        $fixture->setAuthor('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/comment');
        self::assertSame(0, $this->commentRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
