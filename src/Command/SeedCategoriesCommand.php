<?php

namespace App\Command;

use App\Entity\ProductCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-categories',
    description: 'Seed initial product categories',
)]
class SeedCategoriesCommand extends Command
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

        $categories = [
            'TÉLÉPHONES',
            'ACCESSOIRES',
            'CÂBLES & CHARGEURS',
            'AUDIO & SON',
            'PROTECTIONS (GLACES & COQUES)',
            'CARTES MÉMOIRES & CLÉS USB',
            'SERVICES',
            'DIVERS'
        ];

        foreach ($categories as $catName) {
            // Check if exists
            $exists = $this->entityManager->getRepository(ProductCategory::class)->findOneBy(['name' => $catName]);
            
            if (!$exists) {
                $category = new ProductCategory();
                $category->setName($catName);
                $this->entityManager->persist($category);
                $io->text("Pre-persisting: $catName");
            } else {
                $io->text("Skipping: $catName (already exists)");
            }
        }

        $this->entityManager->flush();

        $io->success('Categories seeded successfully.');

        return Command::SUCCESS;
    }
}
