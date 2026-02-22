<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Throwable;

/**
 * Shared bulk action service for delete (and future ban/activate) operations.
 *
 * Replaces the identical loop-based bulk delete pattern found in 8+ controllers.
 */
class BulkActionService
{
    use LocatorAwareTrait;

    /**
     * Parse and sanitize bulk action input from a request.
     *
     * @param mixed $rawIds Raw ids from request data.
     * @return array<int>
     */
    public function sanitizeIds(mixed $rawIds): array
    {
        $ids = is_array($rawIds) ? $rawIds : [];

        return array_values(array_unique(array_filter(
            array_map('intval', $ids),
            static fn($v) => $v > 0,
        )));
    }

    /**
     * Bulk delete entities by id from the given table.
     *
     * @param \Cake\ORM\Table|string $table Table instance or table name.
     * @param array<int> $ids Entity ids to delete.
     * @return array{deleted: int, failed: int}
     */
    public function bulkDelete(Table|string $table, array $ids): array
    {
        if (is_string($table)) {
            $table = $this->fetchTable($table);
        }

        $deleted = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $entity = $table->get((string)$id);
                if ($table->delete($entity)) {
                    $deleted++;
                } else {
                    $failed++;
                }
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    /**
     * Bulk update a single field on entities by id.
     *
     * @param \Cake\ORM\Table|string $table Table instance or table name.
     * @param array<int> $ids Entity ids to update.
     * @param string $field Field name.
     * @param mixed $value New value.
     * @return int Number of affected rows.
     */
    public function bulkUpdateField(Table|string $table, array $ids, string $field, mixed $value): int
    {
        if (empty($ids)) {
            return 0;
        }

        if (is_string($table)) {
            $table = $this->fetchTable($table);
        }

        return $table->updateAll(
            [$field => $value],
            ['id IN' => $ids],
        );
    }
}
