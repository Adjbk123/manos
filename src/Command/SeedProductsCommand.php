<?php

namespace App\Command;

use App\Entity\Product;
use App\Entity\ProductCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-products',
    description: 'Seed initial products',
)]
class SeedProductsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $productsData = [
            ['name' => 'iPhone 13 - 128GB', 'category' => 'TÉLÉPHONES', 'desc' => 'Smartphone Apple, état neuf'],
            ['name' => 'Samsung Galaxy S21', 'category' => 'TÉLÉPHONES', 'desc' => 'Smartphone Samsung Android'],
            ['name' => 'Infinix Hot 10', 'category' => 'TÉLÉPHONES', 'desc' => 'Entrée de gamme performant'],
            ['name' => 'Tecno Spark 7', 'category' => 'TÉLÉPHONES', 'desc' => 'Bonne autonomie'],
            ['name' => 'AirPods Pro', 'category' => 'AUDIO & SON', 'desc' => 'Écouteurs sans fil avec réduction de bruit'],
            ['name' => 'Ecouteurs Bluetooth JBL', 'category' => 'AUDIO & SON', 'desc' => 'Basses puissantes'],
            ['name' => 'Chargeur Rapide 20W', 'category' => 'CÂBLES & CHARGEURS', 'desc' => 'Pour iPhone et Android récents'],
            ['name' => 'Câble USB-C vers Lightning', 'category' => 'CÂBLES & CHARGEURS', 'desc' => '1 mètre, blanc'],
            ['name' => 'Coque Silicone iPhone 13', 'category' => 'PROTECTIONS (GLACES & COQUES)', 'desc' => 'Protection antichoc transparente'],
            ['name' => 'Carte Mémoire 32GB', 'category' => 'CARTES MÉMOIRES & CLÉS USB', 'desc' => 'Classe 10, haute vitesse'],
        ];

        $repoCategory = $this->entityManager->getRepository(ProductCategory::class);
        $repoProduct = $this->entityManager->getRepository(Product::class);

        foreach ($productsData as $data) {
            // Find category
            $category = $repoCategory->findOneBy(['name' => $data['category']]);
            
            if (!$category) {
                // Try literal match or partial? Let's just create it if missing or warn?
                // The previous seeder should have run, but let's be safe.
                $category = new ProductCategory();
                $category->setName($data['category']);
                $this->entityManager->persist($category);
            }

            // Check if product exists
            $exists = $repoProduct->findOneBy(['name' => $data['name']]);
            
            if (!$exists) {
                $product = new Product();
                $product->setName($data['name']);
                $product->setCategory($category);
                $product->setDescription($data['desc']);
                $product->setStockQuantity(0); // Initial stock 0
                
                $this->entityManager->persist($product);
                $io->text("Created: " . $data['name']);
            } else {
                $io->text("Skipped: " . $data['name'] . " (already exists)");
            }
        }

        $this->entityManager->flush();

        $io->success('Products seeded successfully (10 items).');

        return Command::SUCCESS;
    }
}
