<?php

namespace AMREU\UserBundle\Controller;

use AMREU\UserBundle\Doctrine\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserController extends AbstractController
{
    private $userManager;
    private $formFactory;
    private $class;
    private $formType;

    public function __construct(string $class, string $formType, UserManager $userManager, FormFactoryInterface $formFactory)
    {
        $this->userManager = $userManager;
        $this->formFactory = $formFactory;
        $this->class = $class;
        $this->formType = $formType;
    }

    public function test()
    {
        return $this->render('@User/user/test.html.twig');
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

    /**
     * @IsGranted("ROLE_ADMIN")
     */
    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = $this->userManager->newEmptyUser();
        $form = $this->formFactory->create($this->formType, $user, [
            'password_change' => true,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $user AMREU\UserBundle\Model\UserInterface */
            $user = $form->getData();

            $existing_email = $this->userManager->findUserByEmail($user->getEmail());
            $existing_username = $this->userManager->findUserByUsername($user->getUsername());
            if (null !== $existing_email || null !== $existing_username) {
                $this->addFlash('error', 'user_exists');
            } else {
                $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
                $user->setActivated(true);
                $this->userManager->updateUser($user);
                $this->addFlash('success', 'user_saved');

                return $this->redirectToRoute('user_controller_new');
            }
        }

        return $this->render('@User/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

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
