<?php

namespace App\Command;

use App\Repository\CommandeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

#[AsCommand(
    name: 'app:relance-retour-materiel',
    description: 'Envoie un email de relance aux clients n\'ayant pas restitué le matériel après 10 jours.',
)]
class RelanceRetourMaterielCommand extends Command
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private MailerInterface $mailer,
        private Environment $twig,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'jours',
            null,
            InputOption::VALUE_OPTIONAL,
            'Nombre de jours avant relance (défaut : 10)',
            10
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $jours = (int) $input->getOption('jours');

        $commandes = $this->commandeRepository->findRetourMaterielEnRetard($jours);

        if (empty($commandes)) {
            $io->success("Aucune relance à envoyer (aucune commande en retard de restitution).");
            return Command::SUCCESS;
        }

        $envoyes = 0;
        $erreurs = 0;

        foreach ($commandes as $commande) {
            $client = $commande->getUtilisateur();

            if (!$client || !$client->getEmail()) {
                $io->warning("Commande #{$commande->getNumeroCommande()} : pas d'email client, ignorée.");
                continue;
            }

            try {
                $html = $this->twig->render('emails/relance_retour_materiel.html.twig', [
                    'commande' => $commande,
                    'client'   => $client,
                    'jours'    => $jours,
                ]);

                $email = (new Email())
                    ->from('contact@vite-gourmand.fr')
                    ->to($client->getEmail())
                    ->subject('Rappel : restitution du matériel — commande ' . $commande->getNumeroCommande())
                    ->html($html);

                $this->mailer->send($email);

                $io->writeln("  ✓ Relance envoyée à {$client->getEmail()} (commande {$commande->getNumeroCommande()})");
                $envoyes++;

            } catch (\Throwable $e) {
                $io->error("Commande #{$commande->getNumeroCommande()} : échec envoi — {$e->getMessage()}");
                $erreurs++;
            }
        }

        $io->success("{$envoyes} relance(s) envoyée(s), {$erreurs} erreur(s).");

        return $erreurs > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
