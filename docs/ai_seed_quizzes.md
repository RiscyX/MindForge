# AI Seed Quizzes

Generate realistic quizzes through the existing AI generator pipeline, with run-token based rollback.

## Command

```bash
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes [options]
```

## Common usage

Generate 10 quizzes and process immediately:

```bash
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --count=10 --questions=10
```

Queue only (no processing yet):

```bash
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --count=10 --enqueue_only --run_token=my-batch-01
```

Process queue manually:

```bash
/opt/lampp/bin/php bin/cake.php ai_requests_process --limit=10
```

Cleanup one run token only:

```bash
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_run=my-batch-01 --cleanup_only
```

Cleanup all AI-seed runs:

```bash
/opt/lampp/bin/php bin/cake.php seed_ai_quizzes --cleanup --cleanup_only
```

## Notes

- Runs are tagged as `source_reference = ai_seed:<run_token>`.
- Cleanup removes generated AI requests, generated tests, related attempts/answers, favorites, and uploaded AI assets for that run.
- If no `--creator_id` is provided, the first active creator/admin user is used.
