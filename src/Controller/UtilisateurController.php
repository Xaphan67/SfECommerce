<?php

namespace App\Controller;

use App\Form\AdresseType;
use App\Form\InfosUtilisateurType;
use Symfony\Component\Form\FormError;
use App\Form\MotDePasseUtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'profil_utilisateur')]
    public function index(): Response
    {
        // Vérifie qu'un utilisateur est connecté
        if ($this->getUser()) {
            // Récupère l'utilisateur actuellement connecté
            $utilisateur = $this->getUser();

            // Génération des formulaires pour modifier les informations personelles de l'utilisateur
            $infosForm = $this->createForm(InfosUtilisateurType::class, $utilisateur);
            $mdpForm = $this->createForm(MotDePasseUtilisateurType::class, $utilisateur);
            $adresseFacturationForm = $this->createForm(AdresseType::class);
            $adresseLivraisonForm = $this->createForm(AdresseType::class);

            // Récupération des commandes de l'utilisateur
            $commandes = $utilisateur->getCommandes();

            $commandesEnCours = [];
            $commandesPassees = [];
            foreach ($commandes as $commande) {
                if ($commande->getEtat() != "panier") {
                    if ($commande->getEtat() != "expédiée") {
                        $total = 0;
                        foreach ($commande->getProduitCommandes() as $produitCommande) {
                            $total += $produitCommande->getProduit()->getPrix() * $produitCommande->getQuantite();
                        }
                        $commandesEnCours[] = ['commande' => $commande, 'total' => $total];
                    } else {
                        $total = 0;
                        foreach ($commande->getProduitCommandes() as $produitCommande) {
                            $total += $produitCommande->getProduit()->getPrix() * $produitCommande->getQuantite();
                        }
                        $commandesPassees[] = ['commande' => $commande, 'total' => $total];
                    }
                }
            }

            // Récupération des adresses de l'utilisateur
            $adressesFacturation = $utilisateur->getAdressesFacturation();
            $adressesLivraison = $utilisateur->getAdressesLivraison();

            return $this->render('utilisateur/index.html.twig', [
                'utilisateur' => $utilisateur,
                'infosUtilisateur' => $infosForm,
                'mdpUtilisateur' => $mdpForm,
                'adresseFacturationFormulaire' => $adresseFacturationForm,
                'adresseLivraisonFormulaire' => $adresseLivraisonForm,
                'commandesEnCours' => $commandesEnCours,
                'commandesPassees' => $commandesPassees,
                'adressesFacturation' => $adressesFacturation,
                'adressesLivraison' => $adressesLivraison
            ]);
        }

        // Redirige vers la page d'accueil
        return $this->redirectToRoute('app_accueil');
    }

    #[Route('utilisateur/modifier', name:'modifier_donnees_utilisateur')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifie qu'un utilisateur est connecté
        if ($this->getUser()) {
            // Récupère l'utilisateur actuellement connecté
            $utilisateur = $this->getUser();

            // Génère et récupère les informations envoyés via le formulaire
            $form = $this->createForm(InfosUtilisateurType::class, $utilisateur);
            $form->handleRequest($request);

            // Vérifie que le formulaire est soumis et est valide
            if ($form->isSubmitted() && $form->isValid()) {
                // Récupère les informations du formulaire
                $formData = $form->getData();

                // Met à jour l'utilisateur en base de donnée
                $entityManager->persist($formData);
                $entityManager->flush();
            }
        }

        // Redirige vers le profil de l'utilisateur
        return $this->redirectToRoute('profil_utilisateur');
    }

    #[Route('utilisateur/modifierMdp', name:'modifier_mdp_utilisateur')]
    public function editMdp(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Vérifie qu'un utilisateur est connecté
        if ($this->getUser()) {
            // Récupère l'utilisateur actuellement connecté
            $utilisateur = $this->getUser();

            // Génère et récupère les informations envoyés via le formulaire
            $form = $this->createForm(MotDePasseUtilisateurType::class, $utilisateur);
            $form->handleRequest($request);

            // Vérifie que le formulaire est soumis et est valide
            if ($form->isSubmitted() && $form->isValid()) {
                // Récupère les informations du formulaire
                $ancienMdp = $form->get('oldPassword')->getData();
                $nouveauMdp = $form->get('password')->getData();

                // Vérifie si le mot de passe saisi correspond au mot de passe actuel de l'utilisateur
                if ($passwordHasher->isPasswordValid($utilisateur, $ancienMdp)) {
                    // Hashe le nouveau mot de passe et modifie le mot de passe actuel de l'utilisateur
                    $nouveauMdpHash = $passwordHasher->hashPassword($utilisateur, $nouveauMdp);
                    $utilisateur->setPassword($nouveauMdpHash);

                    // Met à jour l'utilisateur en base de donnée
                    $entityManager->persist($utilisateur);
                    $entityManager->flush();
                }
            }
        }

        // Redirige vers le profil de l'utilisateur
        return $this->redirectToRoute('profil_utilisateur');
    }
}
