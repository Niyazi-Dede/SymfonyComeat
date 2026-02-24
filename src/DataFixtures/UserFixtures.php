<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Admin
        $admin = new User();
        $admin->setEmail('admin@comeat.fr');
        $admin->setFirstName('Admin');
        $admin->setLastName('Comeat');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin1234'));
        $admin->setAddress('1 rue de la Paix');
        $admin->setCity('Paris');
        $admin->setPostalCode('75001');
        $admin->setPhone('0600000000');
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($admin);
        $this->addReference('user_0', $admin);

        // Clients
        $users = [
            ['alice@example.com',   'Alice',   'Martin',  '12 av. des Fleurs',     'Lyon',       '69001', '0611111111'],
            ['bob@example.com',     'Bob',     'Dupont',  '3 rue du Moulin',       'Marseille',  '13001', '0622222222'],
            ['claire@example.com',  'Claire',  'Leroy',   '8 bd Haussmann',        'Paris',      '75009', '0633333333'],
            ['david@example.com',   'David',   'Bernard', '5 rue de la Liberté',   'Bordeaux',   '33000', '0644444444'],
            ['emma@example.com',    'Emma',    'Petit',   '22 rue des Lilas',      'Nantes',     '44000', '0655555555'],
        ];

        foreach ($users as $i => $data) {
            [$email, $firstName, $lastName, $address, $city, $postalCode, $phone] = $data;

            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->hasher->hashPassword($user, 'password123'));
            $user->setAddress($address);
            $user->setCity($city);
            $user->setPostalCode($postalCode);
            $user->setPhone($phone);
            $user->setCreatedAt(new \DateTimeImmutable('-' . ($i * 10 + 5) . ' days'));
            $user->setUpdatedAt(new \DateTimeImmutable());

            $manager->persist($user);
            $this->addReference('user_' . ($i + 1), $user);
        }

        $manager->flush();
    }
}
