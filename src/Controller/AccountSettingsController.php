<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Form\DeleteAccountType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountSettingsController extends AbstractController
{
    #[Route('/account/settings', name: 'account_settings')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {

        $user = $this->getUser();

        /** FORMULAIRE CHANGEMENT MOT DE PASSE */
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {

            $data = $passwordForm->getData();

            $user->setPassword(
                $passwordHasher->hashPassword($user, $data['newPassword'])
            );

            $em->flush();

            $this->addFlash('success', 'Mot de passe mis à jour.');
            return $this->redirectToRoute('account_settings');
        }

        /** FORMULAIRE SUPPRESSION COMPTE */
        $deleteForm = $this->createForm(DeleteAccountType::class);
        $deleteForm->handleRequest($request);

        if ($deleteForm->isSubmitted() && $deleteForm->isValid()) {

            $em->remove($user);
            $em->flush();

            return $this->redirectToRoute('app_logout');
        }

        return $this->render('account/settings/index.html.twig', [
            'passwordForm' => $passwordForm->createView(),
            'deleteForm' => $deleteForm->createView(),
        ]);
    }
}