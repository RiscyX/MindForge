# Contributing & Development Guide

This document covers everything you need to get MindForge running locally and work on the codebase.

---

## Local setup

See the platform-specific setup guides:

- [Windows setup](setup/Windows.md)
- [Linux setup](setup/Linux.md)
- [General setup overview](setup/setup.md)

**Requirements:** XAMPP / LAMPP with PHP 8.2.12, MySQL 8, Composer.  
Mailtrap is optional but recommended for testing registration and password reset emails.

---

## Custom CLI commands

All commands are run with the LAMPP PHP binary to ensure the `intl` extension is available:

```bash
/opt/lampp/bin/php bin/cake.php <command> [options]
```

### `seed_dummy_data` — Performance test data

Generates realistic datasets (users, quizzes, questions, answers, attempts, logs, favorites) for performance testing. See the full guide: [docs/dummy_data.md](docs/dummy_data.md)

**Quick examples:**

```bash
# Full fresh medium dataset
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --profile=medium --seed=4242 --only=all

# Quizzes only (no attempts)
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --profile=small --only=tests

# Attempts only (on top of existing data)
/opt/lampp/bin/php bin/cake.php seed_dummy_data --profile=large --seed=9001 --only=attempts

# Clean up a specific run token
/opt/lampp/bin/php bin/cake.php seed_dummy_data --cleanup --cleanup_run=<token> --cleanup_only
```

**Options:**

| Option | Description |
|---|---|
| `--profile`, `-p` | Dataset size: `small`, `medium`, `large`, `xl` |
| `--seed`, `-s` | Deterministic random seed for repeatable data |
| `--cleanup`, `-c` | Delete previously generated dummy rows first |
| `--only`, `-o` | Subset to generate: `all`, `tests`, `attempts` |
| `--attempts` | Override attempt count |
| `--cleanup_run` | Rollback only one specific run token |
| `--cleanup_only` | Run cleanup and exit without generating new rows |

Generated records are tagged for safe identification and cleanup:
- User emails: `dummy.*@mindforge.local`
- Test title prefix: `[DUMMY]`

---

### `seed_ai_quizzes` — AI quiz generation seeding

Generates quizzes through the AI pipeline with run-token based rollback. See the full guide: [docs/ai_seed_quizzes.md](docs/ai_seed_quizzes.md)

**Quick examples:**

```bash
# Generate and process 10 quizzes immediately
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --count=10 --questions=10

# Queue only, process later
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --count=10 --enqueue_only --run_token=my-batch-01

# Process the queue manually
/opt/lampp/bin/php bin/cake.php ai_requests_process --limit=10

# Clean up a specific run token
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_run=my-batch-01 --cleanup_only

# Clean up all AI-seed runs
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_only
```

Runs are tagged as `source_reference = ai_seed:<run_token>`. If no `--creator_id` is provided, the first active creator/admin user is used.

---

### `ai_requests_process` — Process pending AI requests

Processes pending AI generation requests and applies ready drafts that have no `test_id` yet.

```bash
/opt/lampp/bin/php bin/cake.php ai_requests_process --limit=10
```

---

### `api_tokens_cleanup` — Clean up expired API tokens

Removes expired API tokens from the database. Suitable for a cron job.

```bash
/opt/lampp/bin/php bin/cake.php api_tokens_cleanup
```

---

### `cleanup_unactivated_users` — Remove zombie accounts

Deletes unactivated user accounts whose activation token has expired. Runs with a configurable grace period.

```bash
# Default grace period
/opt/lampp/bin/php bin/cake.php cleanup_unactivated_users

# Custom grace period
/opt/lampp/bin/php bin/cake.php cleanup_unactivated_users --grace-hours 48
```

Recommended as a nightly cron job:

```cron
0 3 * * * /opt/lampp/bin/php /opt/lampp/htdocs/MindForge/bin/cake cleanup_unactivated_users
```

---

## Additional docs

- [Auth & API tokens](docs/auth.md)
- [Test engine internals](docs/test_engine.md)
- [Dummy data seeding](docs/dummy_data.md)
- [AI quiz generation](docs/ai_seed_quizzes.md)
