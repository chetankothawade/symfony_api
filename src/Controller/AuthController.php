<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthService;
use App\Traits\ApiResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Dto\LoginRequest;
use App\Dto\SignupRequest;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

class AuthController extends AbstractController
{
    use ApiResponse;

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        #[MapRequestPayload] LoginRequest $loginRequest,
        AuthService $authService
    ): JsonResponse
    {
        $result = $authService->login($loginRequest->email, $loginRequest->password, new Request());
        if ($result === false) {
            return $this->error('The email or password is incorrect.', [], Response::HTTP_UNAUTHORIZED);
        }

        if (isset($result['inactive'])) {
            return $this->error('This user account is currently inactive.', [], Response::HTTP_FORBIDDEN);
        }

        $user = $result['user'];

        return $this->success('Login successful.', [
            'token' => $result['token'],
            'user' => $this->serializeUser($user),
        ]);
    }

    #[Route('/api/signup', name: 'api_signup', methods: ['POST'])]
    public function signup(
        #[MapRequestPayload] SignupRequest $signupRequest,
        AuthService $authService
    ): JsonResponse
    {
        $user = $authService->register(
            $signupRequest->name,
            $signupRequest->email,
            $signupRequest->password,
            $signupRequest->phone
        );
        if ($user === false) {
            return $this->error('The email or password is incorrect.', ['email' => ['The email has already been taken.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success('Signup successful.', [
            'user' => $this->serializeUser($user),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->error('Login failed. Please verify your credentials.', [], Response::HTTP_UNAUTHORIZED);
        }

        $now = new \DateTimeImmutable('now');
        $user->setLastLogoutAt($now);
        $user->setUpdatedAt($now);
        $em->flush();

        return $this->success('Logged out successfully.', []);
    }

    #[Route('/api/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request, AuthService $authService): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';

        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'The email field is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'The email must be a valid email address.';
        }

        if ($errors !== []) {
            return $this->error('Validation failed.', $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = $authService->sendResetLink($email);
        if ($token === false) {
            return $this->error('User not found.', ['email' => ['User not found.']], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->success('Password reset link sent.', [
            'token' => $token,
        ]);
    }

    #[Route('/api/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, AuthService $authService): JsonResponse
    {
        $payload = (array) json_decode((string) $request->getContent(), true);
        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $token = isset($payload['token']) ? (string) $payload['token'] : '';
        $password = isset($payload['password']) ? (string) $payload['password'] : '';
        $passwordConfirmation = isset($payload['password_confirmation']) ? (string) $payload['password_confirmation'] : '';

        $errors = [];
        if ($email === '') {
            $errors['email'][] = 'The email field is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'The email must be a valid email address.';
        }
        if ($token === '') {
            $errors['token'][] = 'The token field is required.';
        }
        if ($password === '') {
            $errors['password'][] = 'The password field is required.';
        } elseif (strlen($password) < 6) {
            $errors['password'][] = 'The password must be at least 6 characters.';
        }
        if ($passwordConfirmation === '') {
            $errors['password_confirmation'][] = 'The password confirmation field is required.';
        } elseif ($password !== $passwordConfirmation) {
            $errors['password_confirmation'][] = 'The password confirmation does not match.';
        }

        if ($errors !== []) {
            return $this->error('Validation failed.', $errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $ok = $authService->resetPassword($email, $token, $password);
        if (!$ok) {
            return $this->error('Invalid or expired token.', [], Response::HTTP_BAD_REQUEST);
        }

        return $this->success('Password reset successful.', []);
    }

    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'uuid' => $user->getUuid(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'status' => $user->getStatus(),
            'role' => $user->getRole(),
            'last_login_at' => $user->getLastLoginAt()?->format(DATE_ATOM),
            'last_login_ip' => $user->getLastLoginIp(),
            'last_login_ua' => $user->getLastLoginUa(),
            'created_at' => $user->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $user->getUpdatedAt()?->format(DATE_ATOM),
            'deleted_at' => $user->getDeletedAt()?->format(DATE_ATOM),
        ];
    }
}
