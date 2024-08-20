<?php
namespace App\Sender;

use App\Entity\Campaign;
use App\Exception\CampaignConfigurationException;
use App\Exception\CampaignException;
use App\Model\CampaignProcessorInterface;
use App\Model\CampaignTrackerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UltraMsgSender implements CampaignProcessorInterface {

    const SENDERID = 'routee';

    public function __construct(
        private HttpClientInterface $http,
        private LoggerInterface $logger,
    )
    {
    }

    public function support(string $url): bool
    {
        return self::SENDERID === parse_url($url, PHP_URL_SCHEME);
    }

    public function process(Campaign $campaign, CampaignTrackerInterface $tracker): Campaign
    {
        $url = $tracker->getUrl();
        $http = $this->http;
        $logger = $this->logger;

        $token = '5hj8wvy57s3pqt9d';
        if (!$token) {
            throw new CampaignConfigurationException('Missing token for UltraMsg API.');
        }

        $resp = $http->request('POST', 'https://api.ultramsg.com/instance92732/messages/chat', [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'token' => $token,
                'to' => $campaign->getContact(),
                'body' => $campaign->getMessage(),
            ]),
        ]);

        if ($resp->getStatusCode() != 200) {
            $logger->warning(sprintf('UltraMsg campaign error: message: %s', $resp->getContent()));
            throw new CampaignException($campaign, 'Application cannot send campaign. Try later or contact support.');
        }

        $content = $resp->getContent();
        $this->logger->debug(sprintf('UltraMsg response: %s', $content));

        $result = json_decode($content, true);

        if (isset($result['sent']) && strtolower($result['sent']) == 'true') {
            return $tracker->sent($campaign, $result['id']);
        } elseif (isset($result['sent']) && strtolower($result['sent']) == 'false') {
            return $tracker->failed($campaign, $result['id']);
        }
    
        return $tracker->failed($campaign, null, $result['error']);
    }

    public function getId(): string {
        return self::SENDERID;
    } 
}
