<?php

namespace App\Form;

use App\Entity\User;
use App\Streaming\ServiceCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints as Assert;

class SettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('locale', ChoiceType::class, [
                'label' => 'settings.language',
                'choices' => ['Deutsch' => 'de', 'English' => 'en'],
                'choice_translation_domain' => false,
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'settings.country',
                'help' => 'settings.country_help',
                'choices' => array_combine(array_map(static fn (string $c) => 'country.'.$c, ServiceCatalog::COUNTRIES), ServiceCatalog::COUNTRIES),
            ])
            ->add('services', ChoiceType::class, [
                'label' => 'settings.services',
                'help' => 'settings.services_help',
                'choices' => array_flip(array_map(static fn (array $s) => $s['name'], ServiceCatalog::SERVICES)),
                'choice_translation_domain' => false,
                'choice_attr' => static fn (string $id) => ['style' => '--service: '.ServiceCatalog::color($id)],
                'multiple' => true,
                'expanded' => true,
            ]);

        if (!$options['setup']) {
            $builder
                ->add('email', EmailType::class, ['label' => 'user.email', 'required' => false, 'help' => 'user.email_help'])
                ->add('currentPassword', PasswordType::class, [
                    'label' => 'settings.current_password',
                    'mapped' => false,
                    'required' => false,
                    'attr' => ['autocomplete' => 'current-password'],
                    'constraints' => [new UserPassword(groups: ['password'])],
                ])
                ->add('newPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'mapped' => false,
                    'required' => false,
                    'first_options' => ['label' => 'settings.new_password', 'attr' => ['autocomplete' => 'new-password']],
                    'second_options' => ['label' => 'user.password_repeat', 'attr' => ['autocomplete' => 'new-password']],
                    'invalid_message' => 'user.password.mismatch',
                    'constraints' => [new Assert\Length(min: 8, max: 4096, groups: ['password'])],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'setup' => false,
            'validation_groups' => static fn ($form) => $form->has('newPassword') && $form->get('newPassword')->getData() ? ['Default', 'password'] : ['Default'],
        ]);
        $resolver->setAllowedTypes('setup', 'bool');
    }
}
