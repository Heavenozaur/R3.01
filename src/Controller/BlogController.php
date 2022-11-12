<?php

namespace App\Controller;

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

            return new Response('L\'article a bien &#xE9;t&#xE9; enregistré.');
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

            return new Response('L\'article a bien &#xE9;t&#xE9; modifié.');
        }

        return $this->render('edit.html.twig', [
            'article' => $article,
            'form' => $form->createView()
        ]);
    }

    public function remove($id)
    {
        return new Response('<h1>Delete article: ' .$id. '</h1>');
    }
}