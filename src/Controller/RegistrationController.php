<?php

namespace App\Controller;

use App\Entity\Admin;
use App\Repository\AdminRepository;
use App\Service\UserConfigurationInitializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserConfigurationInitializer $configInitializer,
        #[Autowire('%app.max_users%')]
        private readonly int $maxUsers,
    ) {
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        $registrationOpen = $this->isRegistrationOpen();

        if ($request->isMethod('POST') && $registrationOpen) {
            $username = trim($request->request->get('username', ''));
            $password = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');
            $csrfToken = $request->request->get('_csrf_token', '');

            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                return $this->render('security/register.html.twig', [
                    'error' => 'Invalid CSRF token.',
                    'last_username' => $username,
                    'registration_open' => $registrationOpen,
                ]);
            }

            if ($username === '') {
                return $this->render('security/register.html.twig', [
                    'error' => 'Username is required.',
                    'last_username' => $username,
                    'registration_open' => $registrationOpen,
                ]);
            }

            if ($password !== $passwordConfirm) {
                return $this->render('security/register.html.twig', [
                    'error' => 'Passwords do not match.',
                    'last_username' => $username,
                    'registration_open' => $registrationOpen,
                ]);
            }

            $existing = $this->adminRepository->findOneBy(['username' => $username]);

            if ($existing) {
                return $this->render('security/register.html.twig', [
                    'error' => 'Username is already taken.',
                    'last_username' => $username,
                    'registration_open' => $registrationOpen,
                ]);
            }

            $user = new Admin();
            $user->setUsername($username);
            $user->setRoles(['ROLE_ADMIN']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->configInitializer->initialize($user);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'error' => null,
            'last_username' => '',
            'registration_open' => $registrationOpen,
        ]);
    }

    private function isRegistrationOpen(): bool
    {
        if ($this->maxUsers === 0) {
            return true;
        }

        return $this->adminRepository->count() < $this->maxUsers;
    }
}
