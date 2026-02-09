<?php
declare(strict_types=1);

use Migrations\BaseMigration;

final class SeedCategories extends BaseMigration
{
    public function up(): void
    {
        $adapter = $this->getAdapter();

        $langRows = $adapter->fetchAll("SELECT id, code FROM languages WHERE code IN ('en_US', 'hu_HU')");
        $langIdByCode = [];
        foreach ($langRows as $row) {
            $langIdByCode[(string)$row['code']] = (int)$row['id'];
        }

        $enId = $langIdByCode['en_US'] ?? null;
        $huId = $langIdByCode['hu_HU'] ?? null;

        if (!$enId || !$huId) {
            throw new RuntimeException("Missing required languages (en_US, hu_HU). Run SeedLanguages migration first.");
        }

        $now = date('Y-m-d H:i:s');

        $seeds = [
            [
                'en' => ['name' => 'Frontend', 'description' => 'HTML, CSS, UI and responsive basics.'],
                'hu' => ['name' => 'Frontend', 'description' => 'HTML, CSS, UI es reszponziv alapok.'],
            ],
            [
                'en' => ['name' => 'Backend', 'description' => 'APIs, databases and server-side fundamentals.'],
                'hu' => ['name' => 'Backend', 'description' => 'API-k, adatbazisok es szerver oldali alapok.'],
            ],
            [
                'en' => ['name' => 'JavaScript', 'description' => 'Core language features and async patterns.'],
                'hu' => ['name' => 'JavaScript', 'description' => 'Nyelvi alapok es async mintak.'],
            ],
            [
                'en' => ['name' => 'Mobile', 'description' => 'React Native and mobile app development.'],
                'hu' => ['name' => 'Mobil', 'description' => 'React Native es mobil app fejlesztes.'],
            ],
            [
                'en' => ['name' => 'DevOps', 'description' => 'CI/CD, Docker and deployment practices.'],
                'hu' => ['name' => 'DevOps', 'description' => 'CI/CD, Docker es telepitesi gyakorlatok.'],
            ],
            [
                'en' => ['name' => 'Databases', 'description' => 'SQL, schema design and query performance.'],
                'hu' => ['name' => 'Adatbazisok', 'description' => 'SQL, schema tervezes es teljesitmeny.'],
            ],
            [
                'en' => ['name' => 'Security', 'description' => 'Auth, tokens and secure coding.'],
                'hu' => ['name' => 'Biztonsag', 'description' => 'Auth, tokenek es biztonsagos kodolas.'],
            ],
            [
                'en' => ['name' => 'Algorithms', 'description' => 'Problem solving and data structures.'],
                'hu' => ['name' => 'Algoritmusok', 'description' => 'Problema megoldas es adatszerkezetek.'],
            ],
            [
                'en' => ['name' => 'Testing', 'description' => 'Unit, integration and E2E testing.'],
                'hu' => ['name' => 'Tesztek', 'description' => 'Unit, integracios es E2E teszteles.'],
            ],
            [
                'en' => ['name' => 'Soft Skills', 'description' => 'Communication, planning and teamwork.'],
                'hu' => ['name' => 'Soft skillek', 'description' => 'Kommunikacio, tervezes es csapatmunka.'],
            ],
        ];

        foreach ($seeds as $seed) {
            $enName = (string)$seed['en']['name'];

            $existing = $adapter->fetchAll(
                "SELECT ct.category_id AS id FROM category_translations ct WHERE ct.language_id = {$enId} AND ct.name = '" . addslashes($enName) . "' LIMIT 1"
            );

            if (!empty($existing)) {
                $categoryId = (int)$existing[0]['id'];
            } else {
                $this->table('categories')->insert([
                    [
                        'is_active' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ])->save();

                $last = $adapter->fetchAll('SELECT LAST_INSERT_ID() AS id');
                $categoryId = (int)($last[0]['id'] ?? 0);
            }

            // en translation
            $this->insertTranslationIfMissing(
                $categoryId,
                (int)$enId,
                (string)$seed['en']['name'],
                (string)($seed['en']['description'] ?? ''),
                $now,
            );

            // hu translation
            $this->insertTranslationIfMissing(
                $categoryId,
                (int)$huId,
                (string)$seed['hu']['name'],
                (string)($seed['hu']['description'] ?? ''),
                $now,
            );
        }
    }

    public function down(): void
    {
        $adapter = $this->getAdapter();
        $en = $adapter->fetchAll("SELECT id FROM languages WHERE code = 'en_US' LIMIT 1");
        if (empty($en)) {
            return;
        }
        $enId = (int)$en[0]['id'];

        $names = [
            'Frontend',
            'Backend',
            'JavaScript',
            'Mobile',
            'DevOps',
            'Databases',
            'Security',
            'Algorithms',
            'Testing',
            'Soft Skills',
        ];

        $in = "'" . implode("','", array_map('addslashes', $names)) . "'";
        $rows = $adapter->fetchAll("SELECT category_id FROM category_translations WHERE language_id = {$enId} AND name IN ({$in})");
        if (empty($rows)) {
            return;
        }

        $ids = array_unique(array_map(static fn($r) => (int)$r['category_id'], $rows));
        $idList = implode(',', $ids);

        $this->execute("DELETE FROM category_translations WHERE category_id IN ({$idList})");
        $this->execute("DELETE FROM categories WHERE id IN ({$idList})");
    }

    private function insertTranslationIfMissing(int $categoryId, int $languageId, string $name, string $description, string $now): void
    {
        $adapter = $this->getAdapter();
        $exists = $adapter->fetchAll(
            "SELECT id FROM category_translations WHERE category_id = {$categoryId} AND language_id = {$languageId} LIMIT 1"
        );
        if (!empty($exists)) {
            return;
        }

        $this->table('category_translations')->insert([
            [
                'category_id' => $categoryId,
                'language_id' => $languageId,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'created_at' => $now,
            ],
        ])->save();
    }
}
