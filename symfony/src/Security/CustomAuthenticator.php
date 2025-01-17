<?php

namespace App\Security;

use App\Entity\Dictionary\Company;
use App\Entity\Manager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomAuthenticator extends JWTAuthenticator
{
    public function __construct(
        JWTTokenManagerInterface $jwtManager,
        EventDispatcherInterface $eventDispatcher,
        TokenExtractorInterface $tokenExtractor,
        UserProviderInterface $userProvider,
        TranslatorInterface $translator = null,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct($jwtManager, $eventDispatcher, $tokenExtractor, $userProvider, $translator);
    }

    #[\Override] public function supports(Request $request): ?bool
    {
        return $request->headers->has('authorization');
    }

    #[\Override] public function doAuthenticate(Request $request)
    {
        $token = preg_replace('/Bearer\s+/', '', $request->headers->get('authorization'));

        if (null === $token) {
            // The token header was empty, authentication fails with HTTP Status
            // Code 401 "Unauthorized"
            throw new CustomUserMessageAuthenticationException('No API token provided.');
        }

        $payload = $this->getJwtManager()->parse($token);

        if (!$this->checkAzp($payload)) {
            throw new InvalidTokenException('Claims wrong.');
        }

        try {
            /**
             * Для идентификатора подходит только поле email.
             * Для использования другого идентификатора необходимо изменить ManagerRepository,
             * либо переназначить UserLoaderInterface в services.yaml
             */
            $manager = $this->getUserProvider()->loadUserByIdentifier($payload['email']);
        } catch (UserNotFoundException $e) {
            $manager = new Manager();

            $this->entityManager->persist($manager);
        }

        $manager->username = $payload['preferred_username'] ?? '';
        $manager->lName = $payload['family_name'] ?? '';
        $manager->fName = $payload['given_name'] ?? '';
        $manager->email = $payload['email'] ?? '';
        $manager->roles = $payload['roles'] ?? [];
        $manager->position = $payload['position'] ?? null;

        if (isset($payload['models']['companies'])) {
            foreach ($this->getCompanies($payload['models']['companies']) as $company) {
                $manager->addCompany($company);
            }
        }

        $saved = false;
        while (!$saved) {
            try {
                $this->entityManager->persist($manager);
                $this->entityManager->flush();
                $saved = true;
            } catch (UniqueConstraintViolationException $exception) {
                $manager = $this->getUserProvider()->loadUserByIdentifier($payload['email']);
                $this->entityManager->flush();
                if ($manager !== null) {
                    $saved = true;
                }
            }
        }


        $passport = new SelfValidatingPassport(
            new UserBadge(
                $manager->email
            )
        );

        $passport->setAttribute('payload', $payload);
        $passport->setAttribute('token', $token);

        return $passport;
    }

    private function checkAzp(array $payload)
    {
        return isset($payload['azp']) && $payload['azp'] === 'models-registry';
    }

    private function getCompanies(array $companiesIds): array
    {
        $repository = $this->entityManager->getRepository(Company::class);

        return $repository->findBy(['id' => $companiesIds]);
    }
}
