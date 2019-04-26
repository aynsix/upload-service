<?php

declare(strict_types=1);

namespace App\Command;

use App\User\UserManager;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateUserCommand extends Command
{
    private $userManager;

    public function __construct(UserManager $userManager)
    {
        parent::__construct();

        $this->userManager = $userManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('app:create-user')
            ->setDescription('Creates a user')
            ->addArgument('email', InputArgument::REQUIRED, 'The user email (used a login)')
            ->addOption(
                'password',
                'p',
                InputOption::VALUE_REQUIRED,
                'Define a password (one is generated by default).',
                null
            )
            ->addOption(
                'update-if-exist',
                null,
                InputOption::VALUE_NONE,
                'If user email already exists, just update the password.',
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('User Credentials');
        $email = $input->getArgument('email');

        $user = $this->userManager->findUserByEmail($email);
        if (null === $user) {
            $user = $this->userManager->createUser();
            $user->setEmail($email);
        } elseif (!$input->getOption('update-if-exist')) {
            throw new Exception(sprintf('User with email "%s" already exists', $email));
        }

        if (null === $password = $input->getOption('password')) {
            $password = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
        }
        $user->setPlainPassword($password);

        $headers = ['Email', 'Plain password'];
        $rows = [
            [$user->getEmail(), $user->getPlainPassword()],
        ];

        $this->userManager->encodePassword($user);
        $this->userManager->persistUser($user);
        $user->eraseCredentials();

        $io->table($headers, $rows);

        return 0;
    }
}
