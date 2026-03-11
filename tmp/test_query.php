<?php
require 'vendor/autoload.php';
$kernel = new App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

/** @var \App\Repository\UserRepository $userRepo */
$userRepo = $em->getRepository(App\Entity\User::class);
/** @var \App\Repository\RessourceRepository $ressourceRepo */
$ressourceRepo = $em->getRepository(App\Entity\Ressource::class);

$users = $userRepo->findAll();
foreach ($users as $u) {
    echo "User: " . $u->getEmail() . " (" . $u->getId() . ")\n";
    $authored = $ressourceRepo->findAuthoredByUser($u);
    echo " - Authored: " . count($authored) . "\n";
    $favorites = $ressourceRepo->findFavoritedByUser($u);
    echo " - Favorites: " . count($favorites) . "\n";
}
