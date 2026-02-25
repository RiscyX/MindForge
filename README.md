# MindForge

> Advanced Web Programming and Integrated Web Systems — college project.

MindForge is a full-stack quiz and practice platform built with CakePHP 5. Users can take quizzes, track their progress, and train endlessly in a category-based infinite training mode. Quiz creators can build and manage their own question banks, and an AI-assisted pipeline can generate quizzes automatically.

---

## Live demo

**[https://undefined.stud.vts.su.ac.rs](https://undefined.stud.vts.su.ac.rs)**

> [!NOTE]
> If the link is not working, the university server is likely down. The project can be run locally — see [CONTRIBUTING.md](CONTRIBUTING.md) for setup instructions.

---

## Features

- **Quiz catalog** — browse, filter, and search public quizzes by category and difficulty
- **Quiz engine** — multiple choice, true/false, text, and matching question types with EN/HU translations
- **Infinity Training** — endless practice mode: pick a category and keep answering until you stop
- **Results & stats** — per-attempt result review, score history, and creator-level quiz analytics
- **Favorites** — save quizzes for quick access
- **AI quiz generation** — generate quizzes via an AI pipeline with automatic translation
- **Quiz Creator panel** — dedicated UI for creating and managing quizzes and questions
- **Admin panel** — user, category, difficulty, and role management
- **REST API** — full API surface with Swagger docs (`/swagger`)
- **Mobile-aware** — responsive UI with OS detection banner for the companion mobile app

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, CakePHP 5 |
| Database | MySQL 8 |
| Frontend | Bootstrap 5.3, Bootstrap Icons, vanilla JS |
| Auth | CakePHP Authentication plugin, JWT tokens for API |
| i18n | CakePHP I18n, EN + HU |
| Dev tools | XAMPP / LAMPP, Composer, PHPCS |

---

## Creators

- Balazs Barat
- Richard Vass

---

## Contributing & development setup

See [CONTRIBUTING.md](CONTRIBUTING.md) for local setup instructions, custom CLI commands, and development guides.
