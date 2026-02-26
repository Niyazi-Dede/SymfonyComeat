<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                    new Length(max: 100),
                    new Regex(
                        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
                        message: 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes.'
                    ),
                ],
                'attr' => [
                    'class'     => 'form-control',
                    'pattern'   => "[a-zA-ZÀ-ÿ\\s\\-']+",
                    'title'     => 'Lettres, espaces, tirets et apostrophes uniquement',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 100),
                    new Regex(
                        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
                        message: 'Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.'
                    ),
                ],
                'attr' => [
                    'class'   => 'form-control',
                    'pattern' => "[a-zA-ZÀ-ÿ\\s\\-']+",
                    'title'   => 'Lettres, espaces, tirets et apostrophes uniquement',
                ],
            ])
            ->add('address', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => '12 rue de la Paix',
                    'maxlength'   => 255,
                ],
            ])
            ->add('city', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
                'constraints' => [
                    new Regex(
                        pattern: '/^[a-zA-ZÀ-ÿ\s\-\']*$/u',
                        message: 'La ville ne peut contenir que des lettres, espaces, tirets et apostrophes.'
                    ),
                ],
                'attr' => [
                    'class'     => 'form-control',
                    'placeholder' => 'Paris',
                    'pattern'   => "[a-zA-ZÀ-ÿ\\s\\-']*",
                    'title'     => 'Lettres, espaces, tirets et apostrophes uniquement',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label'    => 'Code postal',
                'required' => false,
                'constraints' => [
                    new Regex(
                        pattern: '/^\d{4,10}$/',
                        message: 'Le code postal doit contenir uniquement des chiffres (4 à 10 chiffres).'
                    ),
                ],
                'attr' => [
                    'class'       => 'form-control',
                    'placeholder' => '75001',
                    'pattern'     => '\\d{4,10}',
                    'inputmode'   => 'numeric',
                    'title'       => 'Chiffres uniquement (4 à 10 chiffres)',
                    'maxlength'   => 10,
                ],
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'constraints' => [
                    new Regex(
                        pattern: '/^[\d\s\+\-\.\(\)]{7,15}$/',
                        message: 'Numéro invalide (ex: 06 00 00 00 00 ou +33 6 00 00 00 00).'
                    ),
                ],
                'attr' => [
                    'class'       => 'form-control',
                    'placeholder' => '06 00 00 00 00',
                    'pattern'     => '[\\d\\s\\+\\-\\.\\(\\)]{7,15}',
                    'inputmode'   => 'tel',
                    'title'       => 'Chiffres, espaces, +, -, (, ) acceptés',
                    'maxlength'   => 15,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
