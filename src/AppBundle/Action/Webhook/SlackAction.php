<?php

namespace AppBundle\Action\Webhook;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use AppBundle\Slack\Client as SlackClient;
use AppBundle\Slack\WebhookParser as SlackWebhookParser;
use AppBundle\DialogFlow\Client as DialogFlowClient;
use AppBundle\DialogFlow\Parser as DialogFlowParser;
use AppBundle\Service\MemberHandler;
use AppBundle\Service\VacationHandler;
use Psr\Log\LoggerInterface;

final class SlackAction
{
    private $slackWebhookToken;
    private $slackClient;
    private $slackWebhookParser;
    private $dialogFlowClient;
    private $dialogFlowParser;
    private $memberHandler;
    private $vacationHandler;
    private $logger;

    public function __construct(
        SlackClient $slackClient,
        SlackWebhookParser $slackWebhookParser,
        DialogFlowClient $dialogFlowClient,
        DialogFlowParser $dialogFlowParser,
        MemberHandler $memberHandler,
        VacationHandler $vacationHandler,
        LoggerInterface $logger,
        string $slackWebhookToken
    ) {
        $this->slackClient = $slackClient;
        $this->slackWebhookParser = $slackWebhookParser;
        $this->dialogFlowClient = $dialogFlowClient;
        $this->dialogFlowParser = $dialogFlowParser;
        $this->memberHandler = $memberHandler;
        $this->vacationHandler = $vacationHandler;
        $this->logger = $logger;
        $this->slackWebhookToken = $slackWebhookToken;
    }

    /**
     * @Route("/api/webhook/slack", defaults={"_format": "json"})
     * @Method("POST")
     */
    public function __invoke(Request $request): Response
    {
        $content = json_decode($request->getContent(), true);
        $this->logger->debug('Request received from slack {content}', ['content' => $content]);

        if (!isset($content['token']) || $content['token'] !== $this->slackWebhookToken) {
            $this->logger->debug('Token received from Slack {token}', ['token' => $content['token'] ?? null]);

            throw new AccessDeniedHttpException('No token given or token is wrong.');
        }

        if (isset($content['type']) && $content['type'] === 'url_verification') {
            return new JsonResponse(['challenge' => $content['challenge']]);
        }

        $this->logger->debug('Webhook received from Slack', ['content' => $content]);

        try {
            $userSlackId = $this->slackWebhookParser->getSenderId($content);
            $member = $this->memberHandler->getOrCreateFromSlackId($userSlackId);

            $dialogFlowResponse = $this->dialogFlowClient->query(
                $this->slackWebhookParser->getMessage($content),
                $userSlackId
            );

            $slackResponse = $this->slackClient->postMessage(
                $this->dialogFlowParser->getSpeech($dialogFlowResponse),
                $this->slackWebhookParser->getChannel($content)
            );

            $vacationDates = $this->dialogFlowParser->getMessageCustomPayload($dialogFlowResponse);
            $this->vacationHandler->create($vacationDates['startDate'], $vacationDates['endDate'], $member);

        } catch (\InvalidArgumentException $e) {
            $this->logger->warning(
                'Response not supported from Slack or DialogFlow: {exception}',
                ['exception' => $e]
            );
        }

        return new Response('', 204);
    }
}
