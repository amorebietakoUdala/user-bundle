<?php

namespace AMREU\UserBundle\Controller;

use AMREU\UserBundle\Controller\BaseController;
use AMREU\UserBundle\Doctrine\UserManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use AMREU\UserBundle\Form\Factory\UserFormFactory;
use Twig\Environment;
use AMREU\UserBundle\Model\UserInterface;

class UserController extends BaseController
{
    private $userManager;
    private $formFactory;
    private $formType;
    private $twig;

    public function __construct(string $formType, UserManager $userManager, UserFormFactory $formFactory, Environment $twig)
    {
        $this->userManager = $userManager;
        $this->formFactory = $formFactory;
        $this->formType = $formType;
        $this->twig = $twig;
    }

    public function list(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->loadQueryParameters($request);
        $users = $this->userManager->findAll();
        $ajax = $this->getAjax();
        if (!$ajax) {
            $loader = $this->twig->getLoader();
            if ( $loader->exists('@User/user/index.html.twig') ) {
                return $this->render('@User/user/index.html.twig', [
                    'users' => $users,
                ]);
            }

            return $this->render('@User/user/list.html.twig', [
                'users' => $users,
            ]);
        } else {
            return $this->render('@User/user/_list.html.twig', [
                'users' => $users,
            ]);            
        }
    }

    public function new(Request $request, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->loadQueryParameters($request);
        $form = $this->formFactory->createForm([
            'password_change' => true,
            'readonly' => false,
            'new' => true,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserInterface $user */
            $user = $form->getData();
            $existing_email = $this->userManager->findUserByEmail($user->getEmail());
            $existing_username = $this->userManager->findUserByUsername($user->getUsername());
            if (null !== $existing_email || null !== $existing_username) {
                $this->addFlash('error', 'user_exists');
            } else {
                $user->setPassword($passwordEncoder->hashPassword($user, $user->getPassword()));
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
        $this->loadQueryParameters($request);
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

    public function edit(Request $request, UserPasswordHasherInterface $passwordEncoder)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->loadQueryParameters($request);
        $form = $this->formFactory->createForm([
            'readonly' => false,
            'password_change' => true,
            'new' => false,
        ]);
        /** @var UserInterface $user */
        $user = $this->userManager->find($request->get('id'));
        $form->setData($user);
        $previousPassword = $user->getPassword();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UserInterface $user */
            $user = $form->getData();
            if ('nopassword' === $user->getPassword()) {
                $user->setPassword($previousPassword);
            } else {
                $user->setPassword($passwordEncoder->hashPassword($user, $user->getPassword()));
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
        $this->loadQueryParameters($request);
        $user = $this->userManager->find($request->get('id'));
        $this->userManager->deleteUser($user->getUsername());
        $this->addFlash('success', 'user_deleted');

        return $this->redirectToRoute('admin_user_index');
    }
}
