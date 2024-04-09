<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Form\CommentType;
use App\Entity\Post;
use App\Form\PostType;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_start_page')]
    public function start(): Response
    {
        return $this->render('main/startpage.html.twig', [
            'controller_name' => 'MainController',      
        ]);
    }
    #[Route('/home', name: 'app_main')]
    public function index(AuthorizationCheckerInterface $authorizationChecker, PostRepository $postRepository): Response
    {   
        $form = $this->createForm(PostType::class, new Post);
        
        $posts = $postRepository->findAllOrderedByDate();
        if (!$authorizationChecker->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('Necesitas Iniciar Sesión.');
        }
        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
            'posts' => $posts,
            'form' => $form->createView(),       
        ]);
    }
    #[Route('/stats', name: 'app_main_stats')]
    public function stats(PostRepository $postRepository): Response
    {   
        
        $posts = $postRepository->findAllPostsOrderedByLikes();
        $postsc = $postRepository->findAllPostsOrderedByComments();
        return $this->render('main/stats.html.twig', [
            'controller_name' => 'MainController',
            'posts' => $posts,
            'postsc' => $postsc,       
        ]);
    }
    
}
