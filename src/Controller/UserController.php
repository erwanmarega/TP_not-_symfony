<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Password\PasswordHasherInterface;  
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    // Création d'un utilisateur (CRUD) avec mot de passe sécurisé
    #[Route('/admin/user/create', name: 'admin_user_create')]
    public function create(Request $request, EntityManagerInterface $em, PasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encoder le mot de passe de l'utilisateur avec le hasher
            $hashedPassword = $passwordHasher->hash($user->getPassword());
            $user->setPassword($hashedPassword);  // Définir le mot de passe haché

            // Enregistrer l'utilisateur dans la base de données
            $em->persist($user);
            $em->flush();

            // Rediriger vers la liste des utilisateurs après la création
            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Liste des utilisateurs
    #[Route('/admin/users', name: 'admin_user_index')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();  // Récupérer tous les utilisateurs
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    // Modifier un utilisateur
    #[Route('/admin/user/{id}/edit', name: 'admin_user_edit')]
    public function edit(Request $request, User $user, EntityManagerInterface $em, PasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si le mot de passe est modifié, le hacher à nouveau
            if ($user->getPassword()) {
                $hashedPassword = $passwordHasher->hash($user->getPassword());
                $user->setPassword($hashedPassword);
            }

            // Enregistrer les modifications dans la base de données
            $em->flush();

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // Supprimer un utilisateur
    #[Route('/admin/user/{id}/delete', name: 'admin_user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        // Supprimer l'utilisateur de la base de données
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('admin_user_index');
    }
}
