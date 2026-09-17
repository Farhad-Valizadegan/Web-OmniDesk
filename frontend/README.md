# OmniDesk AI Frontend

React + TypeScript frontend for the OmniDesk AI unified communication workspace.

## Stack

- React
- TypeScript
- Vite
- Vitest
- Testing Library
- ESLint

## Responsibilities

The frontend currently provides:

- registration, login, logout, and authenticated application state
- the unified communication workspace
- Gmail connection and synchronization controls
- conversation list and detail presentation
- message rendering
- search and filtering
- loading, empty, error, and synchronization states
- light/dark theme behavior

The frontend must not store provider credentials such as Gmail access tokens, refresh tokens, or provider client secrets.

## Requirements

- Node.js 24 or a compatible version supported by the project toolchain
- npm
- running OmniDesk AI backend API

## Installation

From the repository root on Windows PowerShell:

```powershell
npm.cmd --prefix frontend ci
```

From the `frontend` directory:

```powershell
npm.cmd ci
```

## Development Server

From the repository root:

```powershell
npm.cmd --prefix frontend run dev
```

Default local address:

```text
http://127.0.0.1:5173
```

The backend normally runs at:

```text
http://127.0.0.1:8000
```

## Quality Checks

Run frontend tests:

```powershell
npm.cmd --prefix frontend run test
```

Run ESLint:

```powershell
npm.cmd --prefix frontend run lint
```

Run the production build:

```powershell
npm.cmd --prefix frontend run build
```

These checks also run in GitHub Actions for pull requests targeting `main` and for pushes to `main`.

## Main Source Areas

```text
frontend/
├── public/
├── src/
│   ├── components/
│   ├── lib/
│   ├── test/
│   ├── App.tsx
│   ├── App.test.tsx
│   ├── App.css
│   └── main.tsx
├── package.json
├── vite.config.ts
└── tsconfig*.json
```

## API Boundary

Frontend API access is centralized through `src/lib/api.ts`.

The frontend works with provider-neutral conversation and message data where practical. Provider-specific OAuth credentials remain on the backend.

Authentication uses the application's bearer token stored in browser `sessionStorage` for the current session. A `401` response invalidates the local authentication state.

## Current Workspace Limitations

The sidebar currently contains Inbox plus placeholder entries for Starred, Labels, and Settings. Their implementation is tracked in the Product Backlog through PB-039 to PB-042 and must not be treated as completed functionality until selected, implemented, and verified in a future Sprint.

The current label filter presents known Gmail system labels. Dynamic/custom label discovery is tracked separately in PB-041.

## Documentation

Project-level engineering documentation is maintained under [`../docs`](../docs/).

Start with [`../docs/README.md`](../docs/README.md) for the documentation map.
