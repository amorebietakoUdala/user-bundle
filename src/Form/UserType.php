<?php

namespace AMREU\UserBundle\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $password_change = $options['password_change'];
        $builder
            ->add('username', null, [
                'label' => 'user.username',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('firstName', null, [
                'label' => 'user.firstName',
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'user.email',
                'empty_data' => '',
                'constraints' => [
                    new Email(),
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'user.roles',
                'choices' => [
                    'ROLE_USER' => 'ROLE_USER',
                    'ROLE_API' => 'ROLE_API',
                    'ROLE_ADMIN' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => false,
                'required' => true,
            ]);
        if ($password_change) {
            $builder->add('password', RepeatedType::class, [
                'constraints' => [
                    new NotBlank(),
                ],
                'data' => '',
                'required' => true,
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'options' => [
                    'attr' => ['class' => 'password-field'],
                    /* Needed because it gives an error when no value entered.
                     * Needs to be checked in the controller if the password has changed.
                     */
                    'empty_data' => 'nopassword',
                    'required' => true,
                ],
                'first_options' => ['label' => 'user.new_password'],
                'second_options' => ['label' => 'user.repeat_new_password'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'password_change' => false,
        ]);
    }
}
