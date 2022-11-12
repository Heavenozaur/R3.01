<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Form\ArticleType;
use App\Form\RegistrationFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry as PersistenceManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        PersistenceManagerRegistry $doctrine
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordHasher->HashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            

            $em = $doctrine->getManager();
            $em->persist($user);
            $em->flush();

        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    public function removeUser(User $user, Request $request, PersistenceManagerRegistry $doctrine)
    {

        $entityManager = $doctrine->getManager();
        $entityManager->remove($user);
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