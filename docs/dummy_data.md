# Dummy Data Seeding for Performance Tests

This project now includes a CLI command to generate realistic performance datasets (users, quizzes, questions, answers, attempts, logs, favorites).

## Command

Use LAMPP PHP (with `intl`):

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data [options]
```

## Options

- `--profile`, `-p`: dataset size profile (`small`, `medium`, `large`, `xl`)
- `--seed`, `-s`: deterministic random seed (repeatable data shape)
- `--cleanup`, `-c`: delete previously generated dummy rows first
- `--only`, `-o`: generate a subset (`all`, `tests`, `attempts`)
- `--attempts`: override attempt count for a custom run size
- `--cleanup_run`: rollback only one specific run token (with `--cleanup`)
- `--cleanup_only`: run cleanup and exit without generating new rows

## Typical usage

Generate a full fresh medium dataset:

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --profile=medium --seed=4242 --only=all
```

Generate only quizzes/questions/answers (no attempts yet):

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --profile=small --only=tests
```

Add more attempts later to stress stats endpoints:

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --profile=large --seed=9001 --only=attempts
```

Custom attempt volume (faster than full `large` default):

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --profile=large --attempts=60000 --seed=9001 --only=all
```

Rollback only one run token (token is printed at command start):

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --cleanup_run=20260217171749-s20260217
```

Cleanup-only mode (no new data gets inserted):

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --cleanup_run=20260217171749-s20260217 --cleanup_only
```

Clean existing dummy data only:

```bash
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --only=attempts
```

## What gets generated

- Dummy creator and user accounts
- Public quizzes (`tests`) with EN/HU translations
- Mixed question types:
  - `multiple_choice`
  - `true_false`
  - `text`
  - `matching`
- Answers + translations (including matching pair metadata)
- User favorites (tests and categories)
- Activity logs and device logs
- Attempts with realistic finished/in-progress mix and `test_attempt_answers`

## Safe identification and cleanup

Generated records are tagged with deterministic markers:

- User email pattern: `dummy.*@mindforge.local`
- Test title prefix: `[DUMMY]`

`--cleanup` deletes rows based on these markers, including dependent performance data.
`--cleanup_run` allows precise rollback for a single seed run.

## Profile scale (approximate)

- `small`: quick local validation
- `medium`: team/dev environment baseline load
- `large`: heavier API + stats stress
- `xl`: maximal stress for local hardware or staging

Exact row counts vary by randomized shape (question mix and answer counts).

## Suggested benchmark flow

After seeding, measure these endpoints first:

- `GET /api/v1/tests`
- `GET /api/v1/tests/{id}`
- `POST /api/v1/tests/{id}/start`
- `GET /api/v1/attempts/{id}`
- `POST /api/v1/attempts/{id}/submit`
- `GET /api/v1/attempts/{id}/review`
- `GET /api/v1/me/stats/quizzes`

For web pages:

- `/{lang}/tests`
- `/{lang}/tests/{id}/details`
- `/{lang}/my-stats`
- `/{lang}/tests/{id}/stats`
