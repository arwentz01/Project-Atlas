<?php
declare(strict_types=1);
namespace Atlas\Platform\PatientResources\Domain;
final class BrandingProfile{public function __construct(public readonly string $organizationId,public readonly string $displayName,public readonly ?int $logoAttachmentId,public readonly string $contactBlock,public readonly string $footer,public readonly string $primaryColor){}public static function neutral(string $organizationId,string $name):self{return new self($organizationId,$name,null,'','','#176b72');}}
