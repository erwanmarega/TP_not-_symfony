<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Form\ReservationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'reservation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reservations = $em->getRepository(Reservation::class)->findBy(['user' => $this->getUser()]);

        return $this->render('reservation/index.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/new', name: 'reservation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setUser($this->getUser());
            $em->persist($reservation);
            $em->flush();

            return $this->redirectToRoute('reservation_index');
        }

        return $this->render('reservation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}', name: 'reservation_show')]
    public function show(Reservation $reservation): Response
    {
        // L'utilisateur ne peut voir que ses propres réservations
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('reservation/show.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/edit', name: 'reservation_edit', methods: ['GET', 'POST'])]
    public function edit(Reservation $reservation, Request $request, EntityManagerInterface $em): Response
    {
        // L'utilisateur ne peut modifier que ses propres réservations
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('reservation_show', ['id' => $reservation->getId()]);
        }

        return $this->render('reservation/edit.html.twig', [
            'reservation' => $reservation,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/{id}', name: 'reservation_delete', methods: ['POST'])]
    public function delete(Request $request, Reservation $reservation, EntityManagerInterface $em): Response
    {
        // L'utilisateur ne peut supprimer que ses propres réservations
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$reservation->getId(), $request->request->get('_token'))) {
            $em->remove($reservation);
            $em->flush();
        }

        return $this->redirectToRoute('reservation_index');
    }
}