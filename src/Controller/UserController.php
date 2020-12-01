<?php

namespace AMREU\UserBundle\Controller;

use AMREU\UserBundle\Doctrine\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use AMREU\UserBundle\Form\Factory\UserFormFactory;

class UserController extends AbstractController
{
    private $userManager;
    private $formFactory;
    private $formType;

    public function __construct(string $formType, UserManager $userManager, UserFormFactory $formFactory)
    {
        $this->userManager = $userManager;
        $this->formFactory = $formFactory;
        $this->formType = $formType;
    }

    public function list()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $users = $this->userManager->findAll();

        return $this->render('@User/user/list.html.twig', [
            'users' => $users,
        ]);
    }

    public function new(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->formFactory->createForm([
            'password_change' => true,
            'readonly' => false,
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

                return $this->redirectToRoute('admin_user_new');
            }
        }

        return $this->render('@User/user/new.html.twig', [
            'form' => $form->createView(),
            'readonly' => false,
            'new' => true,
            'password_change' => true,
        ]);
    }

    public function show(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->formFactory->createForm([
            'readonly' => true,
            'password_change' => false,
        ]);
        $user = $this->userManager->find($request->get('id'));
        $form->setData($user);

        return $this->render('@User/user/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => true,
            'new' => false,
            'password_change' => false,
        ]);
    }

    public function edit(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $form = $this->formFactory->createForm([
            'readonly' => false,
            'password_change' => true,
        ]);
        /* @var $user AMREU\UserBundle\Model\UserInterface */
        $user = $this->userManager->find($request->get('id'));
        $form->setData($user);
        $previousPassword = $user->getPassword();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /* @var $user AMREU\UserBundle\Model\UserInterface */
            $user = $form->getData();
            if ('nopassword' === $user->getPassword()) {
                $user->setPassword($previousPassword);
            } else {
                $user->setPassword($passwordEncoder->encodePassword($user, $user->getPassword()));
            }
            $this->userManager->updateUser($user);
            $this->addFlash('success', 'user_saved');
        }

        return $this->render('@User/user/edit.html.twig', [
            'form' => $form->createView(),
            'readonly' => false,
            'new' => false,
            'password_change' => true,
        ]);
    }

    public function delete(Request $request)
    {
        $user = $this->userManager->find($request->get('id'));
        $this->userManager->deleteUser($user->getUsername());
        $this->addFlash('success', 'user_deleted');

        return $this->redirectToRoute('admin_user_list');
    }
}
