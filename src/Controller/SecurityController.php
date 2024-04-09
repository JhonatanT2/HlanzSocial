<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\Comment;
use App\Entity\User;
use App\Form\CommentType;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/user/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $form = $this->createForm(CommentType::class, new Comment());
        return $this->render('security/show.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);

    
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/user', name: 'app_user_list', methods: ['GET'])]
    public function listUsers(AuthorizationCheckerInterface $authorizationChecker,UserRepository $userRepository): Response
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->render('main/accesdenied.html.twig');
        }
        // Obtén todos los usuarios del repositorio
        $users = $userRepository->findAll();

        return $this->render('security/index.html.twig', [
            'users' => $users,
        ]);
    }

}
