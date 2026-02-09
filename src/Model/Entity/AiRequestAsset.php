<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $ai_request_id
 * @property string $storage_path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string|null $sha256
 * @property \Cake\I18n\DateTime $created_at
 */
class AiRequestAsset extends Entity
{
    protected array $_accessible = [
        'ai_request_id' => true,
        'storage_path' => true,
        'mime_type' => true,
        'size_bytes' => true,
        'sha256' => true,
        'created_at' => true,
    ];
}
