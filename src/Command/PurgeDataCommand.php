<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:purge-data',
    description: 'Supprime toutes les données de la base (Ventes, Transactions, Prêts, Stocks) sauf les utilisateurs.',
)]
class PurgeDataCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$io->confirm('ATTENTION : Cela va supprimer toutes les transactions, ventes, prêts et stocks. Voulez-vous continuer ?', false)) {
            return Command::SUCCESS;
        }

        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();

        // Tables à vider (ordre important pour les contraintes de clés étrangères)
        $tables = [
            'balance_movements',
            'transactions',
            'appro_requests',
            'loans',
            'sale_items',
            'sales',
            'stock_batches',
            'stock_arrivals',
            'stock_clients',
            'customers',
            'credit_payments',
            'billetages',
            'rapport_sessions',
            'session_resume_details',
            'session_resume_globals',
            'session_services',
            'reset_password_requests',
            'products',
            'accounts',
            'partners',
        ];

        $io->info('Début de la purge des données...');

        // Désactiver les contraintes de clés étrangères
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');

        foreach ($tables as $table) {
            try {
                $connection->executeStatement($platform->getTruncateTableSQL($table));
                $io->writeln("Table <info>$table</info> vidée.");
            } catch (\Exception $e) {
                // Si la table n'existe pas, on ignore
                $io->writeln("Table <comment>$table</comment> ignorée (peut-être inexistante).");
            }
        }

        // Réactiver les contraintes
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');

        $io->success('La base de données a été purgée. Les utilisateurs ont été conservés.');
        $io->note('N\'oubliez pas de relancer les seeders pour les catégories, opérateurs et types d\'opérations si nécessaire.');

        return Command::SUCCESS;
    }
}
