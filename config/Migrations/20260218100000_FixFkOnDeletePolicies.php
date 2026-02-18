<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Fix missing ON DELETE policies that would cause FK constraint errors.
 *
 * - test_attempt_answers.question_id: RESTRICT → SET NULL
 *   (historical attempt rows survive question deletion)
 * - questions.category_id: RESTRICT → SET NULL
 *   (questions survive category deletion, just lose category)
 * - tests.category_id: RESTRICT → SET NULL
 *   (tests survive category deletion, just lose category)
 */
class FixFkOnDeletePolicies extends AbstractMigration
{
    public function up(): void
    {
        // --- test_attempt_answers.question_id ---
        // FK was already dropped in a previous partial run; column is still NOT NULL.
        // Make it nullable, then add the FK with SET NULL.
        $this->execute('ALTER TABLE `test_attempt_answers` MODIFY `question_id` INT(10) UNSIGNED DEFAULT NULL');
        $this->execute('ALTER TABLE `test_attempt_answers` ADD CONSTRAINT `fk_taa_questions` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        // --- questions.category_id ---
        $this->execute('ALTER TABLE `questions` DROP FOREIGN KEY `fk_questions_categories`');
        $this->execute('ALTER TABLE `questions` MODIFY `category_id` INT(10) UNSIGNED DEFAULT NULL');
        $this->execute('ALTER TABLE `questions` ADD CONSTRAINT `fk_questions_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');

        // --- tests.category_id ---
        $this->execute('ALTER TABLE `tests` DROP FOREIGN KEY `fk_tests_categories`');
        $this->execute('ALTER TABLE `tests` MODIFY `category_id` INT(10) UNSIGNED DEFAULT NULL');
        $this->execute('ALTER TABLE `tests` ADD CONSTRAINT `fk_tests_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE `test_attempt_answers` DROP FOREIGN KEY `fk_taa_questions`');
        $this->execute('ALTER TABLE `test_attempt_answers` MODIFY `question_id` INT(10) UNSIGNED NOT NULL');
        $this->execute('ALTER TABLE `test_attempt_answers` ADD CONSTRAINT `fk_taa_questions` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON UPDATE CASCADE');

        $this->execute('ALTER TABLE `questions` DROP FOREIGN KEY `fk_questions_categories`');
        $this->execute('ALTER TABLE `questions` MODIFY `category_id` INT(10) UNSIGNED NOT NULL');
        $this->execute('ALTER TABLE `questions` ADD CONSTRAINT `fk_questions_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE');

        $this->execute('ALTER TABLE `tests` DROP FOREIGN KEY `fk_tests_categories`');
        $this->execute('ALTER TABLE `tests` MODIFY `category_id` INT(10) UNSIGNED NOT NULL');
        $this->execute('ALTER TABLE `tests` ADD CONSTRAINT `fk_tests_categories` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE');
    }
}
