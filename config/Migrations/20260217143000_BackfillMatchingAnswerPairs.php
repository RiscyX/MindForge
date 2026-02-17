<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class BackfillMatchingAnswerPairs extends BaseMigration
{
    public function up(): void
    {
        $this->execute(
            "UPDATE answers a
            JOIN (
                SELECT s.id, s.rn
                FROM (
                    SELECT
                        x.id,
                        x.question_id,
                        @rn := IF(@q = x.question_id, @rn + 1, 1) AS rn,
                        @q := x.question_id AS _q
                    FROM (
                        SELECT a2.id, a2.question_id
                        FROM answers a2
                        JOIN questions q ON q.id = a2.question_id
                        WHERE q.question_type = 'matching'
                        ORDER BY a2.question_id ASC, a2.id ASC
                    ) x
                    JOIN (SELECT @q := 0, @rn := 0) vars
                ) s
            ) ranked ON ranked.id = a.id
            SET
                a.match_side = IF(MOD(ranked.rn, 2) = 1, 'left', 'right'),
                a.match_group = FLOOR((ranked.rn + 1) / 2)
            WHERE
                (a.match_side IS NULL OR a.match_side = '')
                OR a.match_group IS NULL"
        );
    }

    public function down(): void
    {
        // Non-destructive data backfill migration.
    }
}
