<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Controller;

use Stadline\Resamania2Bundle\Lib\GreenPass\Client\TacvClient;
use Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Entity\GreenPass;
use Stadline\Resamania2Bundle\Lib\GreenPass\Exception\TacvCallException;
use Stadline\Resamania2Bundle\Lib\GreenPass\Manager\GreenPassManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{client_token}")
 */
final class GreenPassController
{
    private const PARAM_CONTACT_ID = 'contactId';
    private const PARAM_DCC = 'dcc';
    private const PARAM_TYPE = 'type';

    private const ERROR_INVALID_TYPE = 'api.error.green-pass.invalid-type';
    private const ERROR_INVALID_PASS = 'api.error.green-pass.invalid-pass';
    private const ERROR_MISSING_PARAMETER = 'api.error.green-pass.missing-parameter';

    private GreenPassManager $manager;
    private TacvClient $tacvClient;

    public function __construct(GreenPassManager $manager, TacvClient $tacvClient)
    {
        $this->manager = $manager;
        $this->tacvClient = $tacvClient;
    }

    /**
     * @Route(
     *     name="api_green_pass_post",
     *     path="/green_passes",
     *     methods={"POST"},
     *     defaults={
     *         "_api_resource_class"=GreenPass::class,
     *         "_api_collection_operation_name"="post"
     *     }
     * )
     */
    public function postGreenPass(Request $request): GreenPass
    {
        try {
            $parameters = $this->getParameters($request, self::PARAM_CONTACT_ID, self::PARAM_DCC, self::PARAM_TYPE);
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        if (!\in_array($type = $parameters['type'], [TacvClient::TYPE_QR_CODE, TacvClient::TYPE_2D_DOC])) {
            throw new BadRequestHttpException(self::ERROR_INVALID_TYPE);
        }

        try {
            if (!$this->tacvClient->isValid($parameters['dcc'], $type)) {
                throw new BadRequestHttpException(self::ERROR_INVALID_PASS);
            }
        } catch (TacvCallException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        try {
            $greenPass = $this->manager->create($parameters['contactId']);
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $greenPass;
    }

    /**
     * @Route(
     *     name="api_manual_green_pass_post",
     *     path="/manual_green_passes",
     *     methods={"POST"},
     *     defaults={
     *         "_api_resource_class"=GreenPass::class,
     *         "_api_collection_operation_name"="post_manual"
     *     }
     * )
     */
    public function postManualGreenPass(Request $request): GreenPass
    {
        try {
            $parameters = $this->getParameters($request, self::PARAM_CONTACT_ID);
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        try {
            $greenPass = $this->manager->create($parameters['contactId']);
        } catch (\Throwable $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }

        return $greenPass;
    }

    private function getParameters(Request $request, string ...$params): array
    {
        $parameters = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        foreach ($params as $param) {
            if (null === $foundParam = $parameters[$param]) {
                throw new \Exception(self::ERROR_MISSING_PARAMETER);
            }

            $foundParams[$param] = $foundParam;
        }

        return $foundParams ?? [];
    }
}
