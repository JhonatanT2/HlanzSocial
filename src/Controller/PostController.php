<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Entity\Like;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/post')]
class PostController extends AbstractController
{
    #[Route('/', name: 'app_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository): Response
    {
        return $this->render('post/index.html.twig', [
            'posts' => $postRepository->findAll(),
        ]);
    }

    #[Route('/favoritos', name: 'app_post_favoritos', methods: ['GET'])]
    public function favoritos(PostRepository $postRepository): Response
    {
        return $this->render('post/favorites.html.twig', [
            'posts' => $postRepository->findAllFavoritosByUser($this->getUser()),
        ]);
    }

    #[Route('/notifis', name: 'app_post_notifis', methods: ['GET'])]
    public function notifications(PostRepository $postRepository): Response
    {
        return $this->render('post/notifications.html.twig', [
            'posts' => $postRepository->findAllByUser($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setIduser($this->getUser());
            $post->setDate(new \DateTime());
            

            $postFile = $form->get('image')->getData();
            if ($postFile) {
                $originalFilename = pathinfo($postFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$postFile->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $postFile->move(
                        $this->getParameter('post_path'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $post->setImage($newFilename);
            }
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/new.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        $form = $this->createForm(CommentType::class, new Comment());
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_post_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_post_delete', methods: ['POST'])]
    public function delete(AuthorizationCheckerInterface $authorizationChecker,Request $request, Post $post, EntityManagerInterface $entityManager): Response
    {
        if (!$authorizationChecker->isGranted('ROLE_ADMIN') && $this->getUser() !== $post->getIduser()) {
            return $this->render('main/accesdenied.html.twig');
        }
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            //Se eliminan los likes asociados al post antes de eliminar este
            $likes = $entityManager->getRepository(Like::class)->findBy(['post' => $post]);
            foreach ($likes as $like) {
                $entityManager->remove($like);
            }
            //Se eliminan los comentarios asociados al post antes de eliminar este
            $comments = $entityManager->getRepository(Comment::class)->findBy(['idpost' => $post]);
            foreach ($comments as $comment) {
                $entityManager->remove($comment);
            }
            $entityManager->remove($post);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_main', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/search', name: 'search', methods: ['POST'])]
    public function search(Request $request, PostRepository $postRepository): Response
    {
        $query = $request->request->get('query');

        // Realiza la búsqueda de posts en función de la consulta
        $posts = $postRepository->findBySearchQuery($query);

        // Renderiza la vista parcial de los resultados
        $html = $this->renderView('post/search_results.html.twig', ['posts' => $posts]);

        return new JsonResponse(['html' => $html]);
    }
}
