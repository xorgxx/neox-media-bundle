<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Form;

use Neox\MediaBundle\Entity\Media;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MediaPickerType extends AbstractType
{
    public function getParent(): string
    {
        return EntityType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Media::class,
            'choice_label' => fn (Media $media) => $media->getTitle() ?: $media->getOriginalName() ?: $media->getUuid(),
            'placeholder' => 'Choisir un média',
            'attr' => [],
            'accept' => [],
            'sortable' => false,
            'multiple' => false,
            'expanded' => false,
            'mode' => 'widget',
        ]);

        $resolver->setAllowedTypes('accept', 'array');
        $resolver->setAllowedTypes('sortable', 'bool');
        $resolver->setAllowedTypes('multiple', 'bool');
        $resolver->setAllowedValues('mode', ['widget', 'compact']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['multiple']) {
            $builder->resetModelTransformers();
            $builder->resetViewTransformers();

            $builder->addModelTransformer(new CallbackTransformer(
                function ($value) {
                    if ($value === null || $value === '') {
                        return '';
                    }
                    if (is_iterable($value)) {
                        $ids = [];
                        foreach ($value as $m) {
                            if ($m instanceof Media) {
                                $ids[] = $m->getId();
                            } elseif (is_int($m)) {
                                $ids[] = $m;
                            }
                        }

                        return implode(',', $ids);
                    }
                    if ($value instanceof Media) {
                        return (string) $value->getId();
                    }

                    return (string) $value;
                },
                function ($value) {
                    if ($value === null || $value === '') {
                        return [];
                    }
                    $ids = array_filter(array_map('intval', explode(',', (string) $value)));

                    return $ids;
                }
            ));
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['accept'] = $options['accept'];
        $view->vars['sortable'] = $options['sortable'];
        $view->vars['multiple'] = $options['multiple'];
        $view->vars['mode'] = $options['mode'];

        if (isset($view->vars['attr']['data-controller'])) {
            unset($view->vars['attr']['data-controller']);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'neox_media_picker';
    }
}
