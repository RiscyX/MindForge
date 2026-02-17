# Test Engine Specification

## Purpose
This document defines the canonical quiz engine behavior for MindForge.
It ensures web, API, and mobile clients rely on the same rules for question handling, scoring, randomization, and attempt lifecycle.

## 1. Question Types

Supported question types:
- `multiple_choice`
- `true_false`
- `text`
- `matching`

### 1.1 `multiple_choice`
- One selected option per question (radio-style in current UI).
- Correct if selected `answer_id` exists for the question and `is_correct = true`.

### 1.2 `true_false`
- Stored as regular answers; usually two options.
- Correctness rule is identical to `multiple_choice`.

### 1.3 `text`
- Free-text user input.
- Author must configure at least one accepted answer.
- Evaluation pipeline:
  1. Normalized exact check (case-insensitive, whitespace/punctuation normalized).
  2. If no exact hit, optional AI semantic validation fallback.

### 1.4 `matching`
- Left-right pairing question.
- Answers are modeled with:
  - `match_side` in (`left`, `right`)
  - `match_group` to define the correct pair
- User submission stores pair mapping in `test_attempt_answers.user_answer_payload`.
- Correctness is all-or-nothing at question level.

## 2. Scoring

Stored fields:
- `correct_answers`: number of correct questions
- `total_questions`: number of active questions in the attempt
- `score`: percentage

Formula:
`score = (correct_answers / total_questions) * 100`

Notes:
- Stored with 2 decimal precision.
- Every question contributes one unit (including `matching`).

## 3. Randomization

Randomization is enabled with deterministic per-attempt ordering.

### 3.1 Question order
- Questions are shuffled per attempt.
- Order is deterministic for the same attempt (refresh-safe).

### 3.2 Answer order
- Choice-based answers are shuffled per question per attempt.
- Matching sides are shuffled for display (left and right order randomized deterministically).
- Order stays stable within the same attempt.

## 4. Attempt Lifecycle

State model is derived from timestamps/records:
- `in_progress`: attempt exists and `finished_at IS NULL`
- `finished`: answers saved and `finished_at` set
- `cancelled`: attempt explicitly aborted (current web flow uses delete-style abort semantics)

## 5. Persistence Model

## 5.1 Key schema updates
- `answers.match_side` (nullable string)
- `answers.match_group` (nullable int)
- `test_attempt_answers.user_answer_payload` (nullable text/json)
- `attempt_answer_explanations` table for persisted review explanations

## 5.2 Consistency updates
- Legacy `single_choice` values are migrated to `multiple_choice`.
- Matching backfill migration assigns `match_side`/`match_group` for legacy records when needed.

## 6. Validation Rules

## 6.1 Question type validation
- Allowed values: `multiple_choice`, `true_false`, `text`, `matching`.

## 6.2 Answer validation
- `matching` requires coherent pair structure:
  - each group has exactly one left + one right
  - both sides have equal counts
- `text` requires at least one non-empty accepted answer.

## 7. AI-Assisted Features

## 7.1 AI explanation on review
- Users can request “Explain with AI” on review.
- Explanations are persisted in `attempt_answer_explanations`.
- AI request/response metadata is logged in `ai_requests`.
- If AI fails, fallback explanation is generated and saved.
- Rate limit applies (`AI_EXPLANATION_DAILY_LIMIT`).

## 7.2 AI semantic fallback for `text` answers
- If normalized exact check fails, AI can validate semantic equivalence.
- Requests are logged as `type = text_answer_evaluation` in `ai_requests`.
- Daily rate limit applies (`AI_TEXT_EVALUATION_DAILY_LIMIT`).

## 7.3 Document-assisted AI generation
- Quiz creator can optionally upload source documents (PDF, DOCX, ODT, TXT, MD, CSV, JSON, XML).
- Extracted text is appended to generation context.
- Supported in web generation and async creator pipeline.

## 8. API and Web Behavior Alignment

Web submit and API submit follow the same correctness rules for all types.
Review payloads expose enough detail for UI rendering while preventing pre-submit leakage of matching solution internals.

## 9. Acceptance Criteria

Done when:
- all clients use the same question/scoring/lifecycle rules,
- randomization behavior is deterministic and documented,
- matching works end-to-end (create -> take -> submit -> review),
- text answers support practical correctness beyond strict character-for-character equality,
- AI explanation and logging persist reliably.
