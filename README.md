# Meeting Intelligence Platform — Executive One‑Page Summary

Purpose
- Turn meeting video, audio, and text into clear outcomes without re‑watching calls.
- Automatically extract Action Items with owners and deadlines, plus a concise summary and key decisions.

What it does (at a glance)
- Accepts input as: video/audio upload or pasted transcript.
- Sends content to an AI service (FastAPI) and returns structured results:
  - Summary
  - Decisions
  - Action Items: [{ task, owner?, deadline? }]
- Runs processing in the background; UI stays responsive.
- Multi‑tenant by design: every record is scoped to an Organization; super‑admins can operate across orgs.

Why it matters
- Saves time and boosts accountability by capturing who will do what by when.
- Standardized outputs enable search, reporting, and integrations with task tools.

How it works (workflow)
1) Create a Meeting Summary and choose input type (Text or Media).
2) System queues a background job to call FastAPI.
3) Results are saved back and shown in the admin (status: pending → processing → done/failed).

Key outputs (data)
- summary: string
- decisions: string[]
- action_items: { task, owner?, deadline? }[]
- processing_status, error_message, source, azure_raw

FastAPI integration (brief)
- Endpoints used:
  - POST {FASTAPI_URL}/summarize for text
  - POST {FASTAPI_URL}/summerize-media for uploads (multipart)
- Client: app\app\Services\FastApiClient.php (extended timeouts, clear errors)
- Background job: app\app\Jobs\SummarizeMediaJob.php (normalizes results)
- Record creation/dispatch: app\app\Filament\Resources\MeetingSummaryResource\Pages\CreateMeetingSummary.php

Multi‑tenancy notes
- organization_id is enforced on create for non‑super‑admins; super‑admins may choose an org.
- Listings, widgets, and events are scoped by organization to keep tenant data isolated.

Use it (quick start)
1) Set FASTAPI_URL in .env (or config/services.php).
2) Ensure queue worker is running (php artisan queue:work).
3) In admin, create a Meeting Summary, upload media or paste text, submit, and return after processing.

Customization
- Add auth headers to FastApiClient if your FastAPI requires API keys.
- Map action_items to Jira/Asana/Trello via jobs or webhooks.

That’s it — a focused, tenant‑aware solution to extract actions, owners, and deadlines from meetings.
