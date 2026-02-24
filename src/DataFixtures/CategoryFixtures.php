<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    private const CATEGORIES = [
        ['name' => 'Fruits & Légumes',     'description' => 'Fruits et légumes frais de saison, directement des producteurs locaux.'],
        ['name' => 'Viandes & Poissons',   'description' => 'Sélection de viandes et poissons de qualité supérieure.'],
        ['name' => 'Produits Laitiers',    'description' => 'Fromages, yaourts, crèmes et laits artisanaux.'],
        ['name' => 'Boulangerie',          'description' => 'Pains, viennoiseries et pâtisseries maison.'],
        ['name' => 'Épicerie Fine',        'description' => 'Huiles, conserves, épices et produits d\'exception.'],
        ['name' => 'Boissons',             'description' => 'Jus naturels, eaux aromatisées et boissons artisanales.'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $index => $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setDescription($data['description']);
            $category->setSlug($this->slugify($data['name']));
            $category->setCreatedAt(new \DateTimeImmutable());
            $category->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($category);
            $this->addReference('category_' . $index, $category);
        }

        $manager->flush();
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ü', 'ç', '&', ' '],
            ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'u', 'c', '',  '-'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\-]+/', '', $text);
        return trim(preg_replace('/-+/', '-', $text), '-');
    }
}
