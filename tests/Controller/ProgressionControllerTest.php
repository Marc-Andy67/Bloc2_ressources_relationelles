<?php

namespace App\Tests\Controller;

use App\Entity\Progression;
use App\Entity\Category;
use App\Entity\Ressource;
use App\Entity\User;
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
        /** @var EntityManagerInterface $manager */
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->manager = $manager;
        $this->progressionRepository = $this->manager->getRepository(Progression::class);

        foreach ($this->progressionRepository->findAll() as $object) {
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
        self::assertPageTitleContains('Progression index');
    }

    /**
     * Routes non implémentées dans le routeur :
     * - /progression/new    → app_progression_new    (inexistante)
     * - /progression/{id}   → app_progression_show   (inexistante)
     * - /progression/{id}/edit → app_progression_edit (inexistante)
     * - /progression/{id}/delete → app_progression_delete (inexistante)
     *
     * Ces tests seront à activer une fois les routes créées dans ProgressionController.
     */
    public function testNew(): void
    {
        $this->markTestSkipped('Route app_progression_new inexistante — à implémenter dans ProgressionController');
    }

    public function testShow(): void
    {
        $this->markTestSkipped('Route app_progression_show inexistante — à implémenter dans ProgressionController');
    }

    public function testEdit(): void
    {
        $this->markTestSkipped('Route app_progression_edit inexistante — à implémenter dans ProgressionController');
    }

    public function testRemove(): void
    {
        $this->markTestSkipped('Route app_progression_delete inexistante — à implémenter dans ProgressionController');
    }
}