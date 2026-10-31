<?php

namespace App\Form;

use App\Entity\WatchList;
use App\Enum\ListType;
use App\Streaming\ServiceCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WatchListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'list.name', 'attr' => ['placeholder' => 'list.name_placeholder']])
            ->add('icon', ChoiceType::class, [
                'label' => 'list.icon',
                'expanded' => true,
                'choices' => array_combine(WatchList::ICONS, WatchList::ICONS),
                'choice_translation_domain' => false,
            ])
            ->add('type', EnumType::class, [
                'class' => ListType::class,
                'label' => 'list.type',
                'expanded' => true,
                'choice_label' => static fn (ListType $type) => 'list.type_'.$type->value,
            ])
            ->add('country', ChoiceType::class, [
                'label' => 'list.country',
                'help' => 'list.country_help',
                'choices' => array_combine(array_map(static fn (string $c) => 'country.'.$c, ServiceCatalog::COUNTRIES), ServiceCatalog::COUNTRIES),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => WatchList::class]);
    }
}
