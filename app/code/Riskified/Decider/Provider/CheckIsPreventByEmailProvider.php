<?php

namespace Riskified\Decider\Provider;

use Riskified\Decider\Api\Config;

class CheckIsPreventByEmailProvider
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function isPreventByEmail($email)
    {
        $trustedEmails = json_decode($this->config->getTrustedEmails(), true);
        $emails = [];

        foreach ($trustedEmails as $trustedEmail) {
            $emails[] = $trustedEmail['email'];
        }

        $isPrevent = !in_array($email, $emails, true);

        return $isPrevent;
    }
}