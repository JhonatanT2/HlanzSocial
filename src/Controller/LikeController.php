<?php

namespace App\Controller;

use App\Entity\Like;
use App\Form\LikeType;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Post;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/like')]
class LikeController extends AbstractController
{
    #[Route('/', name: 'app_like_index', methods: ['GET'])]
    public function index(AuthorizationCheckerInterface $authorizationChecker, LikeRepository $likeRepository): Response
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->render('main/accesdenied.html.twig');
        }
        return $this->render('like/index.html.twig', [
            'likes' => $likeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_like_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $like = new Like();
        $form = $this->createForm(LikeType::class, $like);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($like);
            $entityManager->flush();

            return $this->redirectToRoute('app_like_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('like/new.html.twig', [
            'like' => $like,
            'form' => $form,
        ]);
    }

    #[Route('/status', name: 'app_like_status', methods: ['POST'])]
    public function status(Request $request, LikeRepository $likeRepository): Response
    {
        $postId = $request->request->get('post_id');
        $userId = $this->getUser();

        $like = $likeRepository->findOneBy(['post' => $postId, 'user' => $userId]);

        $isLiked = $like !== null;

        return new JsonResponse(['isLiked' => $isLiked]);
        
        return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/add', name: 'app_like_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager,LikeRepository $likeRepository): Response
    {
        
        $user = $this->getUser();
        // jquery si es url, require si es POST--
        $postId = $request->request->get('post_id');
        //se busca el like con los datos pasados en la BD 
        $like = $likeRepository->findOneBy(['post' => $postId, 'user' => $user]);
        //se busca el post al que se ha dado likes para luego saber la cantidad de likes de este
        $post = $entityManager->getRepository(Post::class)->find($postId);
         
        //Logica para verificar si hay un like o no, si hay lo elimina y si no lo añade
        if($like){
            $entityManager->remove($like);
            $entityManager->flush();
        } else {
            $newLike = new Like();
            $newLike->setUser($user);
            $newLike->setPost($post);
            $entityManager->persist($newLike);
            $entityManager->flush();
        }
        //se guarda una variable con la cantidad de likes del post respectivo 
        // y se guarda para usarlo en el JS
        $likesCount = $post->getLikes()->count();
        return new JsonResponse(['likesCount'=> $likesCount]);
     
        //innecesario, solo en caso de crar un like manualmente
        return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);     
    }

    #[Route('/{id}', name: 'app_like_show', methods: ['GET'])]
    public function show(AuthorizationCheckerInterface $authorizationChecker, Like $like): Response
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->render('main/noauthorized.html.twig');
        }
        return $this->render('like/show.html.twig', [
            'like' => $like,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_like_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Like $like, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LikeType::class, $like);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_like_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('like/edit.html.twig', [
            'like' => $like,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_like_delete', methods: ['POST'])]
    public function delete(Request $request, Like $like, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$like->getId(), $request->request->get('_token'))) {
            $entityManager->remove($like);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_like_index', [], Response::HTTP_SEE_OTHER);
    }

}
