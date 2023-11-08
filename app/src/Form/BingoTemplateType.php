<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BingoTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('size', TextType::class);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $size = $data->getSize();

                for ($row = 0; $row < $size * $size; $row++) {
                    $fieldName = 'cell_' . $row;

                    $form->add($fieldName, TextType::class, [
                        'mapped' => false,
                        'data' => $data->getContent()[$row] ?? '',
                        'label' => 'Cell ' . 1+$row,
                    ]);
                }

            }
        );
    }
}
