<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\participant;

use App\Form\RegistationFormCSVType;
use App\Form\RegistrationFormType;
use App\Model\FileModel;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/registration', name: 'registration_')]
class RegistrationController extends AbstractController
{

    #[Route('/register', name: 'register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $participantPasswordHasher,
        EntityManagerInterface $entityManager,
        CampusRepository $campusRepository,
        ParticipantRepository $participantRepository,
    ): Response
    {
        $participant = new participant();
        $form = $this->createForm(RegistrationFormType::class, $participant);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $existingParticipant = $participantRepository->findOneBy(['mail' => $participant->getMail()]);
            if (!$existingParticipant) {
                $participant->setPassword($participantPasswordHasher->hashPassword($participant, $form->get('plainPassword')->getData()));
                $participant->setRoles(['ROLE_USER']);
                $entityManager->persist($participant);
                $entityManager->flush();
                $this->addFlash("success", "Utilisateur inscrit !");
                return $this->redirectToRoute('participant_list');
            } else {
                $this->addFlash('error', 'Ce mail existe déjà !');
            }
    }

        $ajoutfileModel = new FileModel();
        $csvForm = $this->createForm(RegistationFormCSVType::class, $ajoutfileModel);
        $csvForm->handleRequest($request);
        if ($csvForm->isSubmitted() && $csvForm->isValid()) {
            /**
             * @var UploadedFile $file
             */
            $file = $csvForm->get('fichier')->getData();
            if ($file) {
                $x = 0;
                if (($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                        $x++;
                        // pour sauter la 1ère ligne d'entête
                        if ($x > 1)
                        {
                            $participant = new participant();

                            $participant->setPseudo($data[0]);
                            $participant->setNom($data[1]);
                            $participant->setPrenom($data[2]);
                            $participant->setTelephone($data[3]);
                            $participant->setMail($data[4]);
                            $participant->setCampus($campusRepository->find($data[5]));
                            $participant->setRoles(['ROLE_USER']);
                            $participant->setPassword($participantPasswordHasher->hashPassword($participant, 'eni2023'));

                            $entityManager->persist($participant);
                        }
                    }
                    $entityManager->flush();
                    $this->addFlash('success', 'Ajout des utilisateurs réussi');
                }
            }
            return $this->redirectToRoute('participant_list');
        }
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'csvForm' => $csvForm->createView()]);
    }
}