<?php

namespace App\Form;

use App\Entity\User;
use App\Streaming\ServiceCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AdminUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => 'user.username'])
            ->add('email', EmailType::class, ['label' => 'user.email', 'required' => false])
            ->add('admin', CheckboxType::class, ['label' => 'admin.is_admin', 'required' => false])
            ->add('locale', ChoiceType::class, [
                'label' => 'settings.language',
                'choices' => ['Deutsch' => 'de', 'English' => 'en'],
                'choice_translation_domain' => false,
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'settings.country',
                'choices' => array_combine(array_map(static fn (string $c) => 'country.'.$c, ServiceCatalog::COUNTRIES), ServiceCatalog::COUNTRIES),
            ])
            ->add('setupCompleted', CheckboxType::class, ['label' => 'admin.setup_completed', 'required' => false])
            ->add('newPassword', PasswordType::class, [
                'label' => 'admin.new_password',
                'help' => 'admin.new_password_help',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [new Assert\Length(min: 8, max: 4096)],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
