# FaceRec

Laravel + React (Inertia) app for person enrollment with face recognition. Enrollment images are stored locally and embedded via an external face recognition microservice.

## Stack

- **Backend:** Laravel 13, PHP 8.3+
- **Frontend:** React, Inertia.js, Vite, TypeScript
- **Auth:** Laravel Fortify
- **Face API:** InsightFace + ONNX Runtime service (`FACE_API_URL`, default `http://localhost:8088`)

## Requirements

- PHP 8.3+, Composer
- Node.js 20+ and npm
- SQLite (default) or another database configured in `.env`
- Face recognition service running and reachable (for embeddings)

## Quick start

```bash
composer setup
php artisan storage:link
composer run dev
```

App: [http://localhost:8000](http://localhost:8000)

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

- **Enrollments** — CRUD for people and their photos (auth required)
- **Search by face** — 1:N identify from photo or video against stored embeddings
- **Face embeddings** — automatic on image upload via `FaceRecognitionClient`
- **Users** — admin-only user management
- **Settings** — profile, password/security, appearance

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
app/Repositories/EnrollmentRepository.php
config/services.php                      # face.* config from FACE_API_*
database/migrations/                     # includes embedding columns
resources/js/pages/                      # Inertia React pages (includes enrollments/search)
```
