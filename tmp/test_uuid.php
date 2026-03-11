<?php
require 'vendor/autoload.php';
(new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__.'/../.env');
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$userRepo = $em->getRepository(App\Entity\User::class);
$ressourceRepo = $em->getRepository(App\Entity\Ressource::class);

$user = $userRepo->findOneBy(['email' => 'admin.claire@reseau.fr']);
echo "User ID (string): " . $user->getId() . "\n";
echo "User ID (hex): " . bin2hex($user->getId()->toBinary()) . "\n";

$conn = $em->getConnection();
$sql = 'SELECT HEX(id), HEX(author_id) FROM ressource';
$stmt = $conn->executeQuery($sql);
$rows = $stmt->fetchAllAssociative();
echo "DB Rows:\n";
print_r($rows);

$qb = $ressourceRepo->createQueryBuilder('r')
    ->andWhere('r.author = :user')
    ->setParameter('user', $user->getId()->toBinary()); // Test passing raw binary
$authored = $qb->getQuery()->getResult();
echo "Authored count with raw binary param: " . count($authored) . "\n";
