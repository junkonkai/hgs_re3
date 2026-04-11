# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

HGN (Horror Game Network) is a community-driven horror game database and social platform at horrorgame.net. Built with Laravel 12 (PHP 8.3+) backend, Blade templating for server-side rendering, and a custom TypeScript frontend (no React/Vue — vanilla TS with a `HgnTree` singleton). UI is styled with TailwindCSS. The application UI is primarily in Japanese.

## Commands

### Development
```bash
npm install && php composer.phar install   # Install all dependencies
npm run dev                        # Start Vite dev server (HMR)
php artisan serve                  # Start Laravel dev server
```

### Build
```bash
npm run build                      # Production Vite build
```

### Testing
```bash
php artisan test                   # Run all PHP tests
php artisan test --filter=TestName # Run a single PHP test

npm run test:e2e                   # Playwright E2E (Chromium)
npm run test:e2e:ui                # Playwright interactive UI
npm run test:stg                   # Run E2E against staging
npm run test:e2e:report            # View HTML test report
```

### Database
```bash
php artisan migrate                # Run migrations
php artisan db:seed                # Seed database
php artisan tinker                 # Interactive REPL
```

## Architecture

### Backend (Laravel 12)
- **Routes:** `routes/web.php` (Blade views), `routes/api.php` (REST `/api/v1/*`)
- **Controllers:** Split into `Admin/`, `Api/`, and `User/` namespaces
- **Models:** 40+ Eloquent models for game data (`GameTitle`, `GameFranchise`, `GamePlatform`, etc.)
- **Auth:** Laravel Sanctum (PAT tokens) + GitHub OAuth2 via Socialite
- **Authorization:** Custom `UserRole` enum with `is_admin_user()` helper
- **Search:** Meilisearch via Laravel Scout
- **Helpers:** Global functions in `app/helpers.php` (e.g., `menu_active`, `is_admin_user`, synonym normalization)

### Frontend (TypeScript)
- Entry point: `resources/ts/app.ts`
- **No framework** — custom singleton `HgnTree` class (`resources/ts/hgn-tree.ts`) manages all frontend state and interactions
- Components live in `resources/ts/components/`, animations in `resources/ts/animation/`
- CSS: TailwindCSS in `resources/css/`, compiled via Vite

### Rust Tools (`/src/hgn_rust_tools`)
- 別リポジトリ（`/src/hgn_rust_tools`）に Rust 製のマイクロツール群がある
- **ogp-generator**: OGP画像をサーバーサイドで生成するバイナリ。Laravel の Queue Job から呼び出す
- 詳細は `docs/claude/ogp-generator.md` を参照

### Branches & Deployment
- `main` → production (auto-deploys via GitHub Actions SSH)
- `develop` → staging (auto-deploys via GitHub Actions SSH)
- Feature branches merge into `develop`

## Feature Documentation

機能の実装詳細（使い方・クラス設計・追加手順など）は `docs/claude/` 配下に機能ごとのファイルとして書く。CLAUDE.md には書かない。

@docs/claude/frontend-conventions.md
@docs/claude/discord-webhook.md
@docs/claude/ogp-generator.md

## Interaction Rules
- プロンプトに全角の「？」が含まれる場合は、実装を行わず回答のみ行う。半角の「?」はこの対象外。

## Key Conventions
- PSR-4 autoloading under the `App\` namespace
- Form validation via Laravel Form Requests (`app/Http/Requests/`)
- Game Master API uses Sanctum token abilities configured via `GAME_MASTER_API_TOKEN_ABILITY` env var
- Default database is SQLite (see `.env.example`); configurable for other drivers
