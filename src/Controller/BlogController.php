<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class BlogController extends AbstractController
{
    public function index(PersistenceManagerRegistry $doctrine)
    {
        $articles = $doctrine->getRepository(Article::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        return $this->render('index.html.twig', ['articles' => $articles]);
    }


    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function add(Request $request, PersistenceManagerRegistry $doctrine)
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article->setLastUpdateDate(new \DateTime());

            if ($article->getPicture() !== null) {
                $file = $form->get('picture')->getData();
                $fileName =  uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'), // Le dossier dans lequel le fichier va être chargé
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $article->setPicture($fileName);
            }

            if ($article->getIsPublished()) {
                $article->setPublicationDate(new \DateTime());
            }

            $em = $doctrine->getManager(); 
            $em->persist($article); 
            $em->flush(); 

            $articles = $doctrine->getRepository(Article::class)->findBy(
                ['isPublished' => true],
                ['publication_date' => 'desc']
            );
    
            return $this->render('index.html.twig', ['articles' => $articles]);
        }

        return $this->render('add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    

    /**
     * @Route("/show/{slug}", name="article_show")
     */
    public function show(Article $article)
    {
        return $this->render('show.html.twig', ['article' => $article]);
    }



    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Article $article, Request $request, PersistenceManagerRegistry $doctrine)
    {
        $oldPicture = $article->getPicture();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setLastUpdateDate(new \DateTime());

            if ($article->getIsPublished()) {
                $article->setPublicationDate(new \DateTime());
            }

            if ($article->getPicture() !== null && $article->getPicture() !== $oldPicture) {
                $file = $form->get('picture')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $article->setPicture($fileName);
            } else {
                $article->setPicture($oldPicture);
            }

            $em = $doctrine->getManager();
            $em->persist($article);
            $em->flush();

            $articles = $doctrine->getRepository(Article::class)->findBy(
                [],
                ['last_update_date' => 'desc']
            );
    
            $users = $doctrine->getRepository(User::class)->findAll();
    
            return $this->render('admin/index.html.twig', [
                'articles' => $articles,
                'users' => $users
            ]);
        
        }

        return $this->render('edit.html.twig', [
            'article' => $article,
            'form' => $form->createView()
        ]);
    }

    public function admin(PersistenceManagerRegistry $doctrine)
    {
        $articles = $doctrine->getRepository(Article::class)->findBy(
            [],
            ['last_update_date' => 'desc']
        );

        $users = $doctrine->getRepository(User::class)->findAll();

        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function remove(Article $article, Request $request, PersistenceManagerRegistry $doctrine)
    {


        $entityManager = $doctrine->getManager();
        $entityManager->remove($article);
        //flush the modifications
        $entityManager->flush();

        

        $articles = $doctrine->getRepository(Article::class)->findBy(
            [],
            ['last_update_date' => 'desc']
        );

        $users = $doctrine->getRepository(User::class)->findAll();

        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users
        ]);
    }
    
}