<?php

namespace App\Form;

use App\Entity\Product;
use Neox\MediaBundle\Form\MediaPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('cover', MediaPickerType::class, [
            'accept' => ['image/*'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}
