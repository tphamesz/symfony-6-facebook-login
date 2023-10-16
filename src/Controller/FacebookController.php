<?php

namespace App\Controller;


use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppCustomAuthenticator;

class FacebookController extends AbstractController
{
    #[Route('/facebook', name: 'app_facebook')]
    public function connectAction(ClientRegistry $clientRegistry)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }
        return $clientRegistry
            ->getClient('facebook_main')
            ->redirect([],[
                'public_profile', 'email'
        ]);
    }
 
    
    #[Route('/connect/facebook/check', name: 'connect_facebook_check')]
    public function connectCheckAction(
        Request $request, 
        ClientRegistry $clientRegistry, 
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserAuthenticatorInterface $userAuthenticator,
        AppCustomAuthenticator $authenticator)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }
 
        $client = $clientRegistry->getClient('facebook_main');
         
        try {
             
            $facebookUser = $client->fetchUser();
 
            // check if email exist
            $existingUser  = $entityManager->getRepository(User::class)
                                ->findOneBy(['email' => "terphadanieL@gmail.com"]);
            if($existingUser){
                return $userAuthenticator->authenticateUser(
                    $existingUser,
                    $authenticator,
                    $request
                );
            }
 
            $user = new User();
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $facebookUser->getId()
                )
            );
            $user->setUsername("terphadanieL@gmail.com");
            $entityManager->persist($user);
            $entityManager->flush();
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        } catch (IdentityProviderException $e) {
            var_dump($e->getMessage()); die;
        }
    }
}

