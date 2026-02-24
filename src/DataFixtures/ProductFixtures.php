<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements DependentFixtureInterface
{
    // [name, description, price, stock, category_index]
    private const PRODUCTS = [
        // Fruits & Légumes (0)
        ['Tomates cerises bio',       'Tomates cerises sucrées cultivées en agriculture biologique.',          3.90,  50, 0],
        ['Courgettes de Provence',    'Courgettes fraîches récoltées à la main en Provence.',                  2.50,  40, 0],
        ['Fraises Gariguette',        'Fraises Gariguette parfumées, cueillies à maturité.',                   5.50,  30, 0],
        ['Avocat Hass',               'Avocats crémeux idéaux pour vos guacamoles et salades.',                1.80,  60, 0],

        // Viandes & Poissons (1)
        ['Entrecôte Angus',           'Entrecôte de bœuf Angus maturée 21 jours, goût intense.',             18.90,  20, 1],
        ['Saumon fumé Atlantique',    'Saumon fumé au bois de hêtre, tranché finement.',                      12.50,  25, 1],
        ['Poulet fermier Label Rouge','Poulet élevé en plein air, chair tendre et savoureuse.',                9.90,  15, 1],
        ['Crevettes royales',         'Crevettes décortiquées, idéales pour wok et pasta.',                    8.50,  35, 1],

        // Produits Laitiers (2)
        ['Comté AOP 18 mois',         'Comté affiné 18 mois en caves franc-comtoises, notes fruitées.',        6.90,  40, 2],
        ['Yaourt nature artisanal',   'Yaourt au lait entier, fermentation lente, texture crémeuse.',           2.20,  80, 2],
        ['Beurre de baratte demi-sel','Beurre fabriqué à la baratte avec sel de Guérande.',                    3.50,  50, 2],
        ['Mozzarella di Bufala',      'Mozzarella au lait de bufflonne, importée d\'Italie.',                   4.80,  30, 2],

        // Boulangerie (3)
        ['Pain au levain',            'Pain au levain naturel, cuit au four à bois, croûte dorée.',             3.20,  20, 3],
        ['Croissant pur beurre',      'Croissant feuilleté au beurre AOP, croustillant à souhait.',             1.40,  40, 3],
        ['Tarte tatin',               'Tarte tatin aux pommes caramélisées, pâte brisée maison.',               5.90,  15, 3],
        ['Brioche Nanterre',          'Brioche moelleuse à la mie filante, au beurre et aux œufs frais.',       4.50,  18, 3],

        // Épicerie Fine (4)
        ['Huile d\'olive extra vierge','Huile d\'olive première pression à froid, fruité vert intense.',        9.90,  30, 4],
        ['Miel de lavande AOP',       'Miel de lavande de Haute-Provence, arôme floral délicat.',               7.50,  25, 4],
        ['Fleur de sel de Guérande',  'Fleur de sel récoltée à la main dans les marais salants.',               4.20,  45, 4],

        // Boissons (5)
        ['Jus de pomme artisanal',    'Jus de pomme pressé à froid, sans sucre ajouté ni conservateur.',        3.80,  60, 5],
        ['Limonade artisanale',       'Limonade pétillante au citron de Menton, légèrement sucrée.',             2.90,  55, 5],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRODUCTS as $index => $data) {
            [$name, $description, $price, $stock, $categoryIndex] = $data;

            $product = new Product();
            $product->setName($name);
            $product->setDescription($description);
            $product->setPrice((string) $price);
            $product->setStock($stock);
            $product->setSlug($this->slugify($name));
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setUpdatedAt(new \DateTimeImmutable());

            /** @var \App\Entity\Category $category */
            $category = $this->getReference('category_' . $categoryIndex, \App\Entity\Category::class);
            $product->addCategory($category);

            $manager->persist($product);
            $this->addReference('product_' . $index, $product);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [CategoryFixtures::class];
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ù', 'û', 'ü', 'ç', '\'', ' '],
            ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'u', 'c',  '-',  '-'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\-]+/', '', $text);
        return trim(preg_replace('/-+/', '-', $text), '-');
    }
}
