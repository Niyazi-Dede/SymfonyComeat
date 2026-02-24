<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categories')]
class CategoryPublicController extends AbstractController
{
    #[Route('', name: 'app_categories')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();

        return $this->render('category_public/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', requirements: ['id' => '\d+'])]
    public function show(Category $category, ProductRepository $productRepository): Response
    {
        $products = $productRepository->createQueryBuilder('p')
            ->innerJoin('p.categories', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId())
            ->getQuery()
            ->getResult();

        return $this->render('category_public/show.html.twig', [
            'category' => $category,
            'products' => $products,
        ]);
    }
}
