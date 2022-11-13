<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Video;
use App\Form\VideoType;
use HltvApi\Client;
use App\Repository\VideoRepository;
use App\Repository\ArticleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Validator;
use Webmozart\Assert\Assert;
use HltvApi\Entity\MatchDetails;
use HltvApi\Entity\Matchs;
use Sunra\PhpSimple\HtmlDomParser;



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
            
            $videos = $doctrine->getRepository(Video::class)->findBy(
                ['isPublished' => true],
                ['publication_date' => 'desc']
            );
    
            $users = $doctrine->getRepository(User::class)->findAll();
            return $this->render('admin/index.html.twig', [
                'articles' => $articles,
                'users' => $users,
                'videos' => $videos
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
        
        $videos = $doctrine->getRepository(Video::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        $users = $doctrine->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users,
            'videos' => $videos
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
        
        $videos = $doctrine->getRepository(Video::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        $users = $doctrine->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users,
            'videos' => $videos
        ]);
    }


    public function news(PersistenceManagerRegistry $doctrine)
    {
        $articles = $doctrine->getRepository(Article::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        return $this->render('news.html.twig', ['articles' => $articles]);
    }


    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function video_add(Request $request, PersistenceManagerRegistry $doctrine)
    {
        $video = new Video();
        $form = $this->createForm(VideoType::class, $video);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $video->setLastUpdateDate(new \DateTime());

            if ($video->getVideo() !== null) {
                $file = $form->get('video')->getData();
                $fileName =  uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('videos_directory'), // Le dossier dans lequel le fichier va être chargé
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $video->setVideo($fileName);
            }

            if ($video->getIsPublished()) {
                $video->setPublicationDate(new \DateTime());
            }

            $em = $doctrine->getManager(); 
            $em->persist($video); 
            $em->flush(); 

            $videos = $doctrine->getRepository(Video::class)->findBy(
                ['isPublished' => true],
                ['publication_date' => 'desc']
            );
    
            return $this->render('video_show.html.twig', ['video' => $video]);
        }

        return $this->render('video_add.html.twig', [
            'form' => $form->createView()
        ]);
    }



    public function video_index(PersistenceManagerRegistry $doctrine)
    {
        $videos = $doctrine->getRepository(Video::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        return $this->render('video_index.html.twig', ['videos' => $videos]);
    }



    /**
     * @Route("video/show/{slug}", name="video_show")
     */
    public function video_show(Video $video)
    {
        return $this->render('video_show.html.twig', ['video' => $video]);
    }




    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function video_edit(Video $video, Request $request, PersistenceManagerRegistry $doctrine)
    {
        $oldVideo = $video->getVideo();

        $form = $this->createForm(VideoType::class, $video);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $video->setLastUpdateDate(new \DateTime());

            if ($video->getIsPublished()) {
                $video->setPublicationDate(new \DateTime());
            }

            if ($video->getVideo() !== null && $video->getVideo() !== $oldVideo) {
                $file = $form->get('video')->getData();
                $fileName = uniqid(). '.' .$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    return new Response($e->getMessage());
                }

                $video->setVideo($fileName);
            } else {
                $video->setVideo($oldVideo);
            }

            $em = $doctrine->getManager();
            $em->persist($video);
            $em->flush();

            $articles = $doctrine->getRepository(Article::class)->findBy(
                [],
                ['last_update_date' => 'desc']
            ); 
            
            $videos = $doctrine->getRepository(Video::class)->findBy(
                ['isPublished' => true],
                ['publication_date' => 'desc']
            );
    
            $users = $doctrine->getRepository(User::class)->findAll();
            
            return $this->render('admin/index.html.twig', [
                'articles' => $articles,
                'users' => $users,
                'videos' => $videos
            ]);
        }
        return $this->render('video_edit.html.twig', [
            'video' => $video,
            'form' => $form->createView()
        ]);
    }





    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function video_remove(Video $video, Request $request, PersistenceManagerRegistry $doctrine)
    {


        $entityManager = $doctrine->getManager();
        $entityManager->remove($video);
        //flush the modifications
        $entityManager->flush();

        

        $articles = $doctrine->getRepository(Article::class)->findBy(
            [],
            ['last_update_date' => 'desc']
        ); 
        
        $videos = $doctrine->getRepository(Video::class)->findBy(
            ['isPublished' => true],
            ['publication_date' => 'desc']
        );

        $users = $doctrine->getRepository(User::class)->findAll();
        return $this->render('admin/index.html.twig', [
            'articles' => $articles,
            'users' => $users,
            'videos' => $videos
        ]);
    }




    /**
     * @dataProvider additionalData
     */
    public function match()
    {

        $client = new Client();

        $matches = $client->ongoing();
        
        foreach ($matches as $match) {
            echo $match->getTeam1();
            echo $match->getTeam2();
            echo $match->getMatchUrl();
            echo $match->getMatchUrl();
        }
        return $data;
    }
}