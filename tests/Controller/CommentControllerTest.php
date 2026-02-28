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
            'comment[creationDate]' => new \DateTime(),
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

        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'comment[content]' => 'Something New',
            'comment[creationDate]' => new \DateTime(),
            'comment[parent]' => 'Something New',
            'comment[ressource]' => 'Something New',
            'comment[author]' => 'Something New',
        ]);

        self::assertResponseRedirects('/comment');

        $fixture = $this->commentRepository->findAll();

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

        $fixture = new Comment();
        $fixture->setContent('Value');
        $fixture->setCreationDate(new \DateTime());
        $fixture->setRessource($ressource);
        $fixture->setAuthor($user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/comment');
        self::assertSame(0, $this->commentRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
