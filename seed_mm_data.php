<?php

use App\Entity\Operator;
use App\Entity\OperationType;
use App\Entity\UssdCode;
use App\Entity\OperatorBalance;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\KernelInterface;

require __DIR__ . '/vendor/autoload.php';

$kernel = new App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();

// Clean up existing data to avoid duplicates if re-run
$entityManager->createQuery('DELETE FROM App\Entity\UssdCode')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\OperationType')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\OperatorBalance')->execute();
$entityManager->createQuery('DELETE FROM App\Entity\Operator')->execute();

// --- 1. Operators ---
$operatorsData = [
    ['name' => 'Celtiis', 'logo' => 'celtiis_logo.png'],
    ['name' => 'MTN', 'logo' => 'mtn_logo.png'],
    ['name' => 'Moov', 'logo' => 'moov_logo.png'],
];

$operators = [];
foreach ($operatorsData as $data) {
    $op = new Operator();
    $op->setName($data['name']);
    $op->setLogo($data['logo']);
    $entityManager->persist($op);
    $operators[$data['name']] = $op;
}
$entityManager->flush();

// --- 2. Balances (Exemple fourni) ---
$balancesData = [
    ['op' => 'Celtiis', 'type' => 'virtuel', 'balance' => '120000', 'notes' => 'Celtiis Cash'],
    ['op' => 'Celtiis', 'type' => 'physique', 'balance' => '55000', 'notes' => 'Cash en caisse'],
    ['op' => 'MTN', 'type' => 'virtuel', 'balance' => '450000', 'notes' => 'MoMo'],
    ['op' => 'MTN', 'type' => 'physique', 'balance' => '300000', 'notes' => 'Cash en caisse'],
    ['op' => 'Moov', 'type' => 'virtuel', 'balance' => '200000', 'notes' => 'Moov Money'],
    ['op' => 'Moov', 'type' => 'physique', 'balance' => '150000', 'notes' => 'Cash en caisse'],
];

foreach ($balancesData as $data) {
    $balance = new OperatorBalance();
    $balance->setOperator($operators[$data['op']]);
    $balance->setType($data['type']);
    $balance->setBalance($data['balance']);
    $balance->setNotes($data['notes']);
    $entityManager->persist($balance);
}
$entityManager->flush();

// --- 3. Operation Types (Celtiis) ---
$celtiis = $operators['Celtiis'];
$opTypesData = [
    ['name' => 'Dépôt Mobile Money', 'code' => 'DEPOT_MM'],
    ['name' => 'Retrait Mobile Money', 'code' => 'RETRAIT_MM'],
    ['name' => 'Vente crédit', 'code' => 'VENTE_CREDIT'],
    ['name' => 'Vente forfait internet', 'code' => 'VENTE_FORFAIT'],
    ['name' => 'Consultation solde', 'code' => 'CONSULT_SOLDE'],
    ['name' => 'Transfert crédit', 'code' => 'TRANSFERT_CREDIT'],
    ['name' => 'Prêt crédit', 'code' => 'PRET_CREDIT'],
];

$opTypes = [];
foreach ($opTypesData as $data) {
    $type = new OperationType();
    $type->setOperator($celtiis);
    $type->setName($data['name']);
    $type->setCode($data['code']);
    $entityManager->persist($type);
    $opTypes[$data['name']] = $type;
}
$entityManager->flush();

// --- 4. USSD Codes (Celtiis) ---
$ussdData = [
    ['type' => 'Consultation solde', 'template' => '*889*1*1*{code_secr3t}#'],
    ['type' => 'Vente crédit', 'template' => '*889*5*1*229{numero}{montant}{code_secr3t}#'],
    ['type' => 'Retrait Mobile Money', 'template' => '*889*4*1*1*229{numero}{montant}{code}#'],
    ['type' => 'Dépôt Mobile Money', 'template' => '*889*3*1*229{numero}{montant}{code}#'],
    ['type' => 'Vente forfait internet', 'name_spec' => 'Forfait Internet Jour', 'template' => '*889*5*2*2*229{numero}*1*{montant}*{code}#'],
    ['type' => 'Vente forfait internet', 'name_spec' => 'Forfait Internet Semaine', 'template' => '*889*5*2*2*229{numero}*2*{montant}*{code}#'],
];

foreach ($ussdData as $data) {
    $ussd = new UssdCode();
    $ussd->setOperator($celtiis);
    $ussd->setOperationType($opTypes[$data['type']]);
    $ussd->setTemplate($data['template']);
    $ussd->setNotes($data['name_spec'] ?? $data['type']);
    $entityManager->persist($ussd);
}

$entityManager->flush();

echo "Initial data seeded successfully including operator balances!\n";
