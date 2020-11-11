<?php

namespace AMREU\UserBundle\Controller;

use AMREU\UserBundle\Doctrine\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function test()
    {
        $user = $this->userManager->createUser();
        dd($user);
    }

//    public function list(Request $request)
//    {
//        $em = $this->getDoctrine()->getManager();
//        $class = $request->getParameter('class');
//        dd($class);
//        $users = $em->getRepository(User::class)->findAll();
//
//        return $this->render('user/list.html.twig', [
//            'users' => $users,
//        ]);
//    }
//
//    /**
//     * @Route("/user/new", name="admin_user_new")
//     */
//    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder)
//    {
//        $form = $this->createForm(UserType::class, new User(), [
//            'password_change' => true,
//        ]);
//
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            /* @var $user User */
//            $user = $form->getData();
//            $em = $this->getDoctrine()->getManager();
//            $existing_email = $em->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
//            $existing_username = $em->getRepository(User::class)->findOneBy(['username' => $user->getUsername()]);
//            if (null !== $existing_email || null !== $existing_username) {
//                $this->addFlash('error', 'messages.existingUser');
//            } else {
//                $em->persist($user);
//                $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
//                $em->flush();
//                $this->addFlash('success', 'messages.userSaved');
//
//                return $this->redirectToRoute('admin_user_list');
//            }
//        }
//
//        return $this->render('user/new.html.twig', [
//            'form' => $form->createView(),
//            'readonly' => false,
//            'new' => true,
//            'password_change' => true,
//        ]);
//    }
//
//    /**
//     * @Route("/user/{user}", name="admin_user_show")
//     */
//    public function show(User $user)
//    {
//        $form = $this->createForm(UserType::class, $user);
//
//        return $this->render('user/edit.html.twig', [
//            'form' => $form->createView(),
//            'readonly' => true,
//            'new' => false,
//            'password_change' => false,
//        ]);
//    }
//
//    /**
//     * @Route("/user/{user}/edit", name="admin_user_edit")
//     */
//    public function edit(User $user, Request $request, UserPasswordEncoderInterface $passwordEncoder)
//    {
//        $form = $this->createForm(UserType::class, $user, [
//            'password_change' => true,
//        ]);
//        $previousPassword = $user->getPassword();
//
//        $form->handleRequest($request);
//        if ($form->isSubmitted() && $form->isValid()) {
//            /* @var $user User */
//            $user = $form->getData();
//            if ('nopassword' === $user->getPassword()) {
//                $user->setPassword($previousPassword);
//            } else {
//                $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
//            }
//            $em = $this->getDoctrine()->getManager();
//            $em->persist($user);
//            $em->flush();
//            $this->addFlash('success', 'messages.userSaved');
//        }
//
//        return $this->render('user/edit.html.twig', [
//            'form' => $form->createView(),
//            'readonly' => false,
//            'new' => false,
//            'password_change' => true,
//        ]);
//    }
//
//    /**
//     * @Route("/user/{user}/delete", name="admin_user_delete")
//     */
//    public function delete(User $user)
//    {
//        $em = $this->getDoctrine()->getManager();
//        $em->remove($user);
//        $em->flush();
//        $this->addFlash('success', 'messages.userDeleted');
//
//        return $this->redirectToRoute('admin_user_list');
//    }
}
