<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => 'user.username', 'attr' => ['autocomplete' => 'username']])
            ->add('email', EmailType::class, ['label' => 'user.email', 'required' => false, 'help' => 'user.email_help'])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'user.password', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'user.password_repeat', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'user.password.mismatch',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 8, max: 4096), new Assert\NotCompromisedPassword(skipOnError: true)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
