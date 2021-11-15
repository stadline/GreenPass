<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Stadline\Resamania2Bundle\Lib\ClientToken\Doctrine\Entity\ClientTokenAwareInterface;
use Stadline\Resamania2Bundle\Lib\ClientToken\Doctrine\Entity\ClientTokenAwareTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="Stadline\Resamania2Bundle\Lib\GreenPass\Doctrine\Repository\GreenPassRepository")
 * @ORM\Table(name="green_pass")
 * @ApiResource(
 *     attributes={
 *         "normalization_context"={
 *             "groups"={"green_pass_norm"}
 *         },
 *         "order"={"validThrough": "DESC"}
 *     },
 *     collectionOperations={
 *         "get"={"swagger_context"={"summary"="List all Green Pass"}},
 *         "post"={
 *             "route_name"="api_green_pass_post",
 *             "swagger_context"={
 *                 "summary"="Post a Green Pass",
 *                 "parameters"={
 *                     {
 *                         "in"="body",
 *                         "schema"={
 *                             "type"="object",
 *                             "required"={"contactId", "type", "dcc"},
 *                             "properties"={
 *                                 "contactId"={"type"="string", "description"="Contact IRI"},
 *                                 "type"={"type"="string", "description"="Type of code", "enum"={"qrcode","2ddoc"}},
 *                                 "dcc"={"type"="string", "description"="Decoded code"},
 *                             }
 *                         }
 *                     }
 *                 },
 *                 "responses" = {
 *                    "201"={"description"="GreenPass created",
 *                     "schema"={
 *                             "type"="object",
 *                             "properties"={
 *                                 "contactId"={"type"="string", "description"="Contact IRI"},
 *                                 "validThrough"={"type"="date", "description"="GreenPass expiration date"},
 *                             },
 *                         },
 *                    },
 *                    "400"={"description"="Invalid input (missing parameter, invalid type, invalid pass)"},
 *                    "404"={"description"="Resource not found"}
 *                }
 *             }
 *          },
 *         "post_manual"={
 *             "route_name"="api_manual_green_pass_post",
 *             "swagger_context"={
 *                 "summary"="Manually post a Green Pass",
 *                 "parameters"={
 *                     {
 *                         "in"="body",
 *                         "schema"={
 *                             "type"="object",
 *                             "required"={"contactId"},
 *                             "properties"={
 *                                 "contactId"={"type"="string", "description"="Contact IRI"},
 *                             }
 *                         }
 *                     }
 *                 },
 *                 "responses" = {
 *                    "201"={"description"="GreenPass created",
 *                     "schema"={
 *                             "type"="object",
 *                             "properties"={
 *                                 "contactId"={"type"="string", "description"="Contact IRI"},
 *                                 "validThrough"={"type"="date", "description"="GreenPass expiration date"},
 *                             },
 *                         },
 *                    },
 *                    "400"={"description"="Invalid input (missing parameter, invalid type, invalid pass)"},
 *                    "404"={"description"="Resource not found"}
 *                }
 *             }
 *         },
 *     },
 *     itemOperations={
 *         "delete"={"swagger_context"={"summary"="Delete a Green Pass"}},
 *         "get"={"swagger_context"={"summary"="Get a Green Pass"}}
 *     }
 * )
 *
 * @ApiFilter(OrderFilter::class, properties={"validThrough"})
 * @ApiFilter(SearchFilter::class, properties={"contactId"})
 */
class GreenPass implements ClientTokenAwareInterface
{
    use ClientTokenAwareTrait;
    public const GLOBAL_INTERVAL = 'P3D';
    public const CLASS_EVENT_INTERVAL = 'PT3H';

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column
     * @Groups({"green_pass_norm"})
     */
    private string $contactId;

    /**
     * @ORM\Column(type="datetime")
     * @Groups({"green_pass_norm"})
     */
    private \DateTime $validThrough;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     * @Groups({"green_pass_norm"})
     */
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContactId(): string
    {
        return $this->contactId;
    }

    public function setContactId(string $contactId): void
    {
        $this->contactId = $contactId;
    }

    public function getValidThrough(): \DateTime
    {
        return $this->validThrough;
    }

    public function setValidThrough(\DateTime $validThrough): void
    {
        $this->validThrough = $validThrough;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
