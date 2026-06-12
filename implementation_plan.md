# IntuDash — Feature Batch Implementation Plan

A set of 6 targeted improvements across Clients, Campaigns, and Quotes.

---

## Summary of Changes

| # | Feature | Scope |
|---|---------|-------|
| 1 | Fix quote creation — allow creating quotes from the Quotes section | QuoteController, routes, quotes/index view |
| 2 | Client soft-delete: warn about active campaigns, grey out deleted client's data | ClientController, campaigns/index + show views |
| 3 | Campaign delete (never-started) + archive/restore | CampaignController, migration, campaign views |
| 4 | Archive section with "View Archives" button | Campaigns index, new archives route |
| 5 | Prepopulate client rate on campaign create from client's default | Already partially done in `_form.blade.php` — needs JS fix to trigger on page load when client_id is pre-selected |
| 6 | Quote generation: calculate from `estimated_recipients` (set manually) not actual uploaded recipients | QuoteService |

---

## Open Questions

> [!IMPORTANT]
> **Quote creation from the Quotes index** — Currently quotes can only be generated *from a campaign's show page*. Do you want to add a "New Quote" button on the Quotes index that lets you pick a client + campaign (dropdown-based), or should it be a standalone quote builder (no campaign required)?
> My default plan: Add a "Create Quote" modal/page where you pick client → campaign → quantities.

> [!IMPORTANT]
> **Archive behaviour** — When a campaign is archived, should it be a separate `archived` status flag (boolean column) or a true soft-delete? My default: Add an `archived_at` timestamp column. Archived campaigns won't show in the main list but can be restored. This is separate from soft-delete (which is already used for permanent deletion).

> [!IMPORTANT]
> **Client delete with active campaigns** — You asked to "grey out the company name and that whole campaign." Should campaigns of a deleted client:
> - Stay visible in the campaigns list but greyed out / marked "Client Deleted"?
> - Be archived automatically?
> My default plan: keep campaigns visible but grey out the client name with a "Deleted" badge, and block new campaigns for that client.

---

## Proposed Changes

### 1. Quote Creation from Quotes Section

#### [MODIFY] [QuoteController.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Http/Controllers/QuoteController.php)
- Add `create()` — renders a form to pick client + campaign
- Add `store()` — validates and calls `QuoteService::generateFromCampaign()`

#### [NEW] `resources/views/quotes/create.blade.php`
- Select client → dynamically filter campaigns via JS
- On submit, POST to new route

#### [MODIFY] [web.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/routes/web.php)
- Add `GET /quotes/create` → `QuoteController@create`
- Add `POST /quotes` → `QuoteController@store`

#### [MODIFY] [quotes/index.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/quotes/index.blade.php)
- Add "New Quote" button in page header

---

### 2. Client Soft-Delete with Campaign Warning

#### [MODIFY] [ClientController.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Http/Controllers/ClientController.php)
- In `destroy()`: check for active campaigns (not cancelled/completed)
- If found → redirect back with error message listing the campaigns
- Add a `force` parameter: if `?force=1` is sent, soft-delete proceeds (campaigns stay but flagged)

#### [MODIFY] [clients/show.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/clients/show.blade.php)
- Add a delete button with a confirm modal that shows active campaign count
- Modal has two actions: "Cancel" and "Delete Anyway" (only if no active campaigns, or a force-delete warning)

#### [MODIFY] [campaigns/index.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/campaigns/index.blade.php)
- Client name column: if `$campaign->client()->withTrashed()->first()->deleted_at` is set, grey out and add "(Deleted)" badge

#### [MODIFY] [CampaignController.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Http/Controllers/CampaignController.php)
- Change `index()` to use `withTrashed()` on client relationship so deleted-client campaigns still show

#### [MODIFY] [Campaign.php model](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Models/Campaign.php)
- Update `client()` relationship to use `withTrashed()` by default (or add a `clientWithTrashed()` method)

---

### 3. Campaign Delete & Archive

#### [NEW] Migration: `add_archived_at_to_campaigns_table`
- Adds `archived_at` nullable timestamp to `campaigns` table

#### [MODIFY] [Campaign.php model](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Models/Campaign.php)
- Add `isArchived()` helper
- Add `scopeNotArchived()` — excludes archived from the default list
- Add `scopeArchived()` — only archived campaigns

#### [MODIFY] [CampaignController.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Http/Controllers/CampaignController.php)
- `destroy()`: currently allows `draft` and `cancelled`. Add restriction that campaign must have `estimated_recipients == 0` OR never had recipients uploaded (i.e. status is `draft`)
- Add `archive(Campaign $campaign)` — sets `archived_at = now()`
- Add `restore(Campaign $campaign)` — nulls `archived_at`
- Add `archived()` index method — returns only archived campaigns view
- Update `index()` to use `scopeNotArchived()`

#### [MODIFY] [campaigns/show.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/campaigns/show.blade.php)
- Add "Archive" button for completed/cancelled campaigns
- Add "Delete" button (hard delete) only for `draft` campaigns that were never started

#### [MODIFY] [campaigns/index.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/campaigns/index.blade.php)
- Add "Archives" button linking to `/campaigns/archived`

#### [NEW] `resources/views/campaigns/archived.blade.php`
- Same structure as index but for archived campaigns
- Each row has a "Restore" button

#### [MODIFY] [web.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/routes/web.php)
- Add `GET /campaigns/archived` → `CampaignController@archived`
- Add `POST /campaigns/{campaign}/archive` → `CampaignController@archive`
- Add `POST /campaigns/{campaign}/restore` → `CampaignController@restore`

---

### 4. Client Rate Prepopulation Fix

#### [MODIFY] [campaigns/_form.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/campaigns/_form.blade.php)
The JS already listens to the `change` event on the client dropdown, but on page load when `client_id` is pre-selected (via `?client_id=` query param), the rate doesn't auto-fill.

**Fix**: On `DOMContentLoaded`, read the currently selected option's `data-rate` and populate `client_rate_per_sms` if the field is at its default value. Also set `estimated_recipients` pre-population if needed.

---

### 5. Quote Generation: Use `estimated_recipients` (not uploaded recipients)

#### [MODIFY] [QuoteService.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Services/QuoteService.php)

Currently:
```php
$quantity = $campaign->validRecipients()->count() * $campaign->sms_segments;
```

Change to:
```php
$quantity = ($campaign->estimated_recipients ?: $campaign->validRecipients()->count()) * $campaign->sms_segments;
```

This uses `estimated_recipients` when set, falling back to actual uploaded valid recipients.

> [!NOTE]
> This also means the "Generate Quote" button should be available from **draft** campaigns (with just an estimated count set), not only after recipients are uploaded. The current `abort_if` in `QuoteController@generate` blocks this — I'll relax it to include `draft` status when `estimated_recipients > 0`.

#### [MODIFY] [QuoteController.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/app/Http/Controllers/QuoteController.php)
- Relax the `abort_if` status check to allow `draft` status when `estimated_recipients > 0`

#### [MODIFY] [campaigns/show.blade.php](file:///c:/Users/LindokuhleMbhele/OneDrive%20-%20Rokkit%20Digital%20Agency/Documents/Laravel%20Applications/IntuDash/resources/views/campaigns/show.blade.php)
- Show "Generate Quote" button also for `draft` campaigns with `estimated_recipients > 0`

---

## Verification Plan

### Manual Verification
1. Create a quote directly from the Quotes section (no campaign page needed)
2. Delete a client with active campaigns — confirm modal appears with campaign list
3. Delete a client with no active campaigns — confirm it soft-deletes
4. View campaigns list — deleted client's campaigns show greyed out
5. Archive a completed campaign — confirm it disappears from main list
6. Visit `/campaigns/archived` — see archived campaign, restore it
7. Delete a draft campaign that was never started — confirm hard delete works
8. Create a new campaign with client pre-selected — rate auto-populates
9. Create a draft campaign with `estimated_recipients` set, generate quote — quote uses estimated count
