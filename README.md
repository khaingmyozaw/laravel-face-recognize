# FaceRec

Laravel + React (Inertia) app for person enrollment with face recognition. Enrollment images are stored locally and embedded via an external face recognition microservice.

## Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** React, Inertia.js, Vite, TypeScript
- **Auth:** Laravel Fortify (web) + Bearer personal access tokens (mobile API)
- **Face API:** InsightFace + ONNX Runtime service (`FACE_API_URL`, default `http://localhost:8088`)

## Requirements

- Docker Desktop (recommended via Laravel Sail), or PHP 8.3+, Composer, and Node.js 20+
- MySQL (provided by Sail) or another database configured in `.env`
- Face recognition service running and reachable (for embeddings)

## Quick start (Docker / Sail)

App runs on port **7884**.

```bash
cp .env.example .env   # if needed
# Ensure WWWUSER/WWWGROUP are set (numeric IDs). Sail sets them if you use ./vendor/bin/sail.
./vendor/bin/sail build --no-cache
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail artisan storage:link
```

App: [http://localhost:7884](http://localhost:7884)

Useful Sail commands:

```bash
./vendor/bin/sail up -d          # start containers
./vendor/bin/sail down           # stop containers
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev    # Vite HMR (port 5173)
./vendor/bin/sail shell          # shell inside the app container
```

When the Face API runs on the host machine, keep `FACE_API_URL=http://host.docker.internal:8088` so Sail can reach it.

## Quick start (local PHP)

```bash
composer setup
php artisan storage:link
composer run dev
```

App: [http://localhost:8000](http://localhost:8000) (or whatever `APP_URL` / `APP_PORT` you set)

`composer setup` installs PHP/JS deps, copies `.env`, generates the app key, runs migrations, and builds assets. `composer run dev` starts the Laravel app, queue worker, and Vite together.

Optional seed users:

```bash
php artisan db:seed
```

Creates `test1@example.com` (admin) and `gate@example.com` (gate role) via the database seeder.

## Face recognition

On enrollment create/update, each uploaded image is sent to `POST /v1/embed`. When a face is detected, the following are stored on `enrollment_images`:

| Column | Description |
| --- | --- |
| `embedding` | Face vector (JSON) |
| `embedding_dim` | Vector length (e.g. 512) |
| `bbox` | Detected face bounding box |
| `det_score` | Detection confidence |
| `face_count` | Faces found in the image |

If the service is down, the file is unreadable, or no face is found, the image is still saved and embedding fields stay `null` (non-blocking).

### Search by face

Upload a probe photo or video on **Search by face** (`/enrollments/search`).

- **Photo** — calls `POST /v1/identify` on the face service
- **Video** — calls `POST /v1/video/identify` (samples frames, longer timeout)

Both paths load stored embeddings and return ranked enrollment matches with similarity scores.

### Environment

```env
FACE_API_URL=http://localhost:8088
FACE_API_KEY=
FACE_API_TIMEOUT=15
FACE_API_VIDEO_TIMEOUT=120
FACE_IDENTIFY_TOP_K=5
FACE_IDENTIFY_THRESHOLD=
FACE_VIDEO_SAMPLE_FPS=
FACE_VIDEO_MAX_FRAMES=
FACE_VIDEO_MAX_KB=51200
```

Optional `FACE_API_KEY` is sent as `X-API-Key` when set. `FACE_IDENTIFY_THRESHOLD`, `FACE_VIDEO_SAMPLE_FPS`, and `FACE_VIDEO_MAX_FRAMES` are omitted from requests when empty (service defaults apply). Ensure PHP `upload_max_filesize` / `post_max_size` allow videos up to `FACE_VIDEO_MAX_KB` (default 50MB).

### Health check

```bash
curl http://localhost:8088/health
```

Expected shape:

```json
{
  "status": "ok",
  "version": "0.1.0",
  "engine_ready": true,
  "model": "buffalo_l",
  "embedding_dim": 512
}
```

API docs: `http://localhost:8088/docs`

## Main features

- **Enrollments** — CRUD for people and their photos (permission-gated)
- **Search by face** — 1:N identify from photo or video against stored embeddings
- **Face embeddings** — automatic on image upload via `FaceRecognitionClient`
- **Users** — user management (`users.*` permissions; admin role)
- **Roles** — CRUD for roles and permissions (`roles.*`; admin role is protected)
- **Settings** — profile, password/security, appearance
- **Mobile API** — JSON Bearer-token API under `/api` (no Excel import/export)

## Mobile API

Base URL: `/api`. Authenticate with `Authorization: Bearer {token}`.

| Method | Endpoint | Permission | Notes |
| --- | --- | --- | --- |
| POST | `/api/login` | — | Body: `email`, `password`, optional `device_name` |
| GET | `/api/user` | auth | Current user + permissions |
| POST | `/api/logout` | auth | Revokes the current token |
| GET | `/api/enrollments` | `enrollments.view` | Query: `search`, `per_page` |
| POST | `/api/enrollments` | `enrollments.create` | Multipart; optional `images[]` |
| GET | `/api/enrollments/{id}` | `enrollments.view` | Includes images |
| PUT | `/api/enrollments/{id}` | `enrollments.update` | Multipart; optional new `images[]` |
| DELETE | `/api/enrollments/{id}` | `enrollments.delete` | |
| DELETE | `/api/enrollments/{id}/images/{image}` | `enrollments.update` | |
| POST | `/api/enrollments/identify` | `enrollments.search` | Multipart `image` |
| POST | `/api/enrollments/identify-video` | `enrollments.search` | Multipart `video` |
| GET/POST/PUT/DELETE | `/api/users…` | `users.*` | Admin user management |
| GET | `/api/users/roles` | `users.view` | Role options |

Excel import/export stays web-only.

Example login:

```bash
curl -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"gate@example.com","password":"password","device_name":"android"}'
```

## Authorization

Access is **permission-based**. Roles are stored in the database and managed under **Roles** (admin). The built-in `admin` role cannot be edited or deleted.

| Permission | Admin (default) | Gate (default) |
| --- | --- | --- |
| `enrollments.*` | ✓ | ✓ |
| `users.*` | ✓ | |
| `roles.*` | ✓ | |

Checks use Laravel Gates on routes and form requests. Shared Inertia prop `auth.permissions` drives the sidebar and action buttons via `useCan()`. API routes use the same Gates with the `api` Bearer-token guard.

## Useful commands

```bash
# Tests + static analysis
composer test

# Format PHP
composer lint

# Frontend checks
npm run lint:check
npm run types:check
npm run format:check
```

## Project layout

```
app/Services/FaceRecognitionClient.php   # HTTP client for /v1/embed, /v1/identify, /v1/video/identify
app/Services/EnrollmentService.php       # Enrollment, embed on upload, search by image/video
app/Http/Controllers/Api/                # Mobile JSON API controllers
app/Models/Concerns/HasApiTokens.php     # Personal access tokens for mobile auth
app/Repositories/EnrollmentRepository.php
config/services.php                      # face.* config from FACE_API_*
database/migrations/                     # includes embedding + personal_access_tokens
resources/js/pages/                      # Inertia React pages (includes enrollments/search)
routes/api.php                           # Mobile API routes
```
